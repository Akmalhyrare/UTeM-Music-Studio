<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Generates realistic Malaysian university names, emails, matric numbers,
 * phone numbers and diverse demo passwords for seeders/factories, and
 * keeps a log of plaintext-vs-hash pairs for the dev credentials report.
 */
class MalaysianData
{
    /** @var array<int, array{0: string, 1: string}> Majority Malay, plus Chinese/Indian — [full_name, email shortname] */
    private static array $namePool = [
        // Malay - male
        ['Muhammad Amir Hakimi bin Razali', 'amir'],
        ['Ahmad Danial bin Zulkifli', 'danial'],
        ['Mohd Syafiq bin Karim', 'syafiq'],
        ['Amirul Asyraf bin Yusof', 'amirul'],
        ['Hafiz Iskandar bin Mahmud', 'hafiz'],
        ['Muhammad Haziq bin Salleh', 'haziq'],
        ['Wan Mohd Hisyam bin Wan Ahmad', 'hisyam'],
        ['Faris Aiman bin Rosli', 'faris'],
        ['Mohd Zulfadli bin Othman', 'zulfadli'],
        ['Iqbal Hakim bin Anuar', 'iqbal'],
        ['Mohd Firdaus bin Sulaiman', 'firdaus'],
        ['Azri Hafiz bin Mat Som', 'azri'],
        // Malay - female
        ['Nur Aisyah binti Mohd Faiz', 'aisyah'],
        ['Nurul Huda binti Ismail', 'huda'],
        ['Siti Nurhaliza binti Ahmad', 'nurhaliza'],
        ['Aina Sofea binti Abdul Rahman', 'aina'],
        ['Farah Adibah binti Hassan', 'farah'],
        ['Nur Atiqah binti Roslan', 'atiqah'],
        ['Nurul Syazana binti Kamal', 'syazana'],
        ['Wan Nur Izzati binti Wan Zahari', 'izzati'],
        ['Nadia Batrisyia binti Azlan', 'nadia'],
        ['Siti Maisarah binti Yaakob', 'maisarah'],
        // Chinese
        ['Tan Jia Wei', 'jiawei'],
        ['Lim Wei Xuan', 'weixuan'],
        ['Wong Mei Ling', 'meiling'],
        ['Lee Chong Wei', 'chongwei'],
        ['Chong Sze Ying', 'szeying'],
        ['Ng Hui Min', 'huimin'],
        ['Tan Wei Ming', 'weiming'],
        ['Goh Zi Xuan', 'zixuan'],
        // Indian
        ['Raj Kumar a/l Suresh', 'raj'],
        ['Priya Devi a/p Ramesh', 'priya'],
        ['Karthik a/l Subramaniam', 'karthik'],
        ['Deepa a/p Manoharan', 'deepa'],
        ['Vimal Raj a/l Anand', 'vimal'],
        ['Kavitha a/p Murugan', 'kavitha'],
    ];

    /** @var array<int, array{name: string, email: string, role: string, password: string, hash: string}> */
    private static array $credentialLog = [];

    private static int $cursor = 0;

    /** @var array<string, bool> */
    private static array $usedEmails = [];

    /** @var array<string, bool> */
    private static array $usedMatricNos = [];

    /**
     * Shortnames already handed out (across both staff and students). Pre-reserves the
     * shortnames used by the fixed demo accounts (StaffSeeder/StudentSeeder) so random
     * factory-generated records never collide with them, regardless of seeder order.
     *
     * @var array<string, bool>
     */
    private static array $usedShortnames = [
        'faizal'  => true,
        'syazana' => true,
        'amir'    => true,
    ];

    /**
     * Pull the next unused [full_name, shortname] pair from the pool.
     * Once the pool is exhausted, append an incrementing numeric suffix
     * to keep names/shortnames unique. Names already claimed (e.g. by a
     * fixed demo account) are skipped so the same person doesn't end up
     * as both a student and a staff member.
     */
    private static function nextName(): array
    {
        $pool = self::$namePool;
        $poolSize = count($pool);

        do {
            $index = self::$cursor % $poolSize;
            $cycle = intdiv(self::$cursor, $poolSize);
            self::$cursor++;

            [$fullName, $shortname] = $pool[$index];

            if ($cycle > 0) {
                $fullName  .= ' ' . ($cycle + 1);
                $shortname .= ($cycle + 1);
            }
        } while (isset(self::$usedShortnames[$shortname]));

        self::$usedShortnames[$shortname] = true;

        return [$fullName, $shortname];
    }

    public static function uniqueStudentEmail(string $shortname): string
    {
        $email = strtolower($shortname) . '@student.utem.edu.my';
        $suffix = 1;

        while (isset(self::$usedEmails[$email])) {
            $suffix++;
            $email = strtolower($shortname) . $suffix . '@student.utem.edu.my';
        }

        self::$usedEmails[$email] = true;

        return $email;
    }

    public static function uniqueStaffEmail(string $shortname): string
    {
        $email = strtolower($shortname) . '@utem.edu.my';
        $suffix = 1;

        while (isset(self::$usedEmails[$email])) {
            $suffix++;
            $email = strtolower($shortname) . $suffix . '@utem.edu.my';
        }

        self::$usedEmails[$email] = true;

        return $email;
    }

    public static function uniqueMatricNo(): string
    {
        do {
            $digits = str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $matricNo = 'B' . $digits;
        } while (isset(self::$usedMatricNos[$matricNo]));

        self::$usedMatricNos[$matricNo] = true;

        return $matricNo;
    }

    public static function malaysianPhoneNumber(): string
    {
        $prefixes = ['012', '013', '014', '016', '017', '018', '019', '011'];
        $prefix   = $prefixes[array_rand($prefixes)];

        return $prefix . '-' . random_int(1000000, 9999999);
    }

    /**
     * Build a diverse, realistic demo password from a name fragment.
     * Guarantees uppercase, lowercase, digit and symbol characters.
     */
    public static function diversePassword(string $shortname): string
    {
        $name = ucfirst(strtolower($shortname));
        $year = random_int(24, 27);
        $num  = random_int(10, 99);

        $templates = [
            "{$name}@20{$year}",
            "{$name}#UTeM{$year}",
            "{$name}\${$num}",
            "UTeM_{$name}{$year}",
            "{$name}#Campus{$year}",
            "Student@{$name}{$num}",
            "Staff!{$name}{$year}",
            "{$name}_{$num}!",
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Generate a full student profile: name, email, matric number, phone, password.
     */
    public static function studentProfile(): array
    {
        [$fullName, $shortname] = self::nextName();

        $email    = self::uniqueStudentEmail($shortname);
        $password = self::diversePassword($shortname);

        $profile = [
            'full_name' => $fullName,
            'email'     => $email,
            'matric_no' => self::uniqueMatricNo(),
            'phone_no'  => self::malaysianPhoneNumber(),
            'password'  => Hash::make($password),
        ];

        self::logCredential($fullName, $email, 'Student', $password, $profile['password']);

        return $profile;
    }

    /**
     * Generate a full staff profile: name, email, position, phone, password.
     */
    public static function staffProfile(string $position): array
    {
        [$fullName, $shortname] = self::nextName();

        $email    = self::uniqueStaffEmail($shortname);
        $password = self::diversePassword($shortname);

        $profile = [
            'full_name' => $fullName,
            'email'     => $email,
            'position'  => $position,
            'phone_no'  => self::malaysianPhoneNumber(),
            'password'  => Hash::make($password),
        ];

        self::logCredential($fullName, $email, $position, $password, $profile['password']);

        return $profile;
    }

    /**
     * Build a profile for a fixed (non-random) demo account: a known name/email
     * with a freshly generated diverse password, logged for the credentials report.
     */
    public static function fixedAccount(string $fullName, string $email, string $role, string $shortname): array
    {
        $password = self::diversePassword($shortname);
        $hash     = Hash::make($password);

        self::$usedEmails[$email]       = true;
        self::$usedShortnames[$shortname] = true;
        self::logCredential($fullName, $email, $role, $password, $hash);

        return [
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $hash,
            'phone_no'  => self::malaysianPhoneNumber(),
        ];
    }

    /**
     * Build a profile for a fixed (non-random) demo account with an explicit,
     * known plaintext password (e.g. provided by the project owner), logged
     * for the credentials report.
     */
    public static function fixedAccountWithPassword(string $fullName, string $email, string $role, string $password): array
    {
        $hash = Hash::make($password);

        self::$usedEmails[$email] = true;
        self::logCredential($fullName, $email, $role, $password, $hash);

        return [
            'full_name' => $fullName,
            'email'     => $email,
            'password'  => $hash,
            'phone_no'  => self::malaysianPhoneNumber(),
        ];
    }

    public static function logCredential(string $name, string $email, string $role, string $plaintext, string $hash): void
    {
        self::$credentialLog[] = [
            'name'     => $name,
            'email'    => $email,
            'role'     => $role,
            'password' => $plaintext,
            'hash'     => $hash,
        ];
    }

    /**
     * Write the dev-only credentials reference document (markdown table) to
     * storage/app/private/dev-credentials.md. Never expose this in production.
     */
    public static function writeCredentialsLog(): void
    {
        if (empty(self::$credentialLog)) {
            return;
        }

        $lines = [
            '# Development Demo Credentials',
            '',
            '> Generated by database seeders. For local development/testing only — do NOT use in production.',
            '',
            '| Name | Email | Role | Plaintext Demo Password | Hashed Password |',
            '|------|-------|------|--------------------------|------------------|',
        ];

        foreach (self::$credentialLog as $entry) {
            $lines[] = sprintf(
                '| %s | %s | %s | `%s` | `%s` |',
                $entry['name'],
                $entry['email'],
                $entry['role'],
                $entry['password'],
                $entry['hash']
            );
        }

        $path = storage_path('app/private/dev-credentials.md');
        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
