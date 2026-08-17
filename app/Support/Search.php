<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reusable search helper providing partial, case-insensitive, multi-field
 * and typo-tolerant (fuzzy) matching across the application.
 *
 * On PostgreSQL it uses ILIKE for case-insensitive partial matches and the
 * pg_trgm similarity() function for fuzzy/typo-tolerant matches. On other
 * drivers (e.g. SQLite used in tests) it falls back to LIKE only.
 */
class Search
{
    /** Minimum trigram word_similarity for a fuzzy match to count (0-1). */
    private const FUZZY_THRESHOLD = 0.4;

    /**
     * Apply a search term to a query, matching it against the given columns
     * and/or related models. Each word in the term must match somewhere
     * (own columns or any of the given relations); columns within a word
     * are OR'd together.
     *
     * @param Builder $query
     * @param string|null $term
     * @param array<int, string> $columns Own table columns to search.
     * @param array<string, array<int, string>> $relations Relation name => columns on that relation to search (supports dot notation for nested relations).
     */
    public static function apply(Builder $query, ?string $term, array $columns, array $relations = []): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $words = preg_split('/\s+/', $term);
        $isPostgres = DB::connection()->getDriverName() === 'pgsql';

        foreach ($words as $word) {
            $query->where(function (Builder $q) use ($columns, $relations, $word, $isPostgres) {
                self::matchColumns($q, $columns, $word, $isPostgres);

                foreach ($relations as $relation => $relationColumns) {
                    $q->orWhereHas($relation, function (Builder $rq) use ($relationColumns, $word, $isPostgres) {
                        $rq->where(function (Builder $rq2) use ($relationColumns, $word, $isPostgres) {
                            self::matchColumns($rq2, $relationColumns, $word, $isPostgres);
                        });
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Columns excluded from fuzzy (word_similarity) matching even when
     * present in $columns. Email addresses share a common institutional
     * domain (e.g. "@student.utem.edu.my"), so a typo like "stundent" would
     * otherwise fuzzy-match every record's email and return everything.
     * Partial (ILIKE) matching on these columns is unaffected.
     */
    private const NO_FUZZY_COLUMNS = ['email'];

    private static function matchColumns(Builder $query, array $columns, string $word, bool $isPostgres): void
    {
        $likeOperator = $isPostgres ? 'ilike' : 'like';

        foreach ($columns as $column) {
            $query->orWhere($column, $likeOperator, "%{$word}%");

            if ($isPostgres && !in_array($column, self::NO_FUZZY_COLUMNS, true)) {
                // word_similarity() finds the best-matching substring, so a short
                // typo'd word (e.g. "gitar") can still match inside a longer
                // column value (e.g. "Guitar Instrument Cable").
                $query->orWhereRaw("word_similarity(?, {$column}::text) > ?", [$word, self::FUZZY_THRESHOLD]);
            }
        }
    }
}
