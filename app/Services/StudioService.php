<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\StudioImage;
use App\Models\StudioUnavailability;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class StudioService
{
    /** Maximum number of gallery images allowed per studio. */
    private const MAX_IMAGES = 10;

    /** How many days ahead to scan when looking for the next available slot. */
    private const NEXT_SLOT_SEARCH_DAYS = 14;

    public function __construct(private BookingService $bookingService)
    {
    }

    public function createStudio(array $data): Studio
    {
        return Studio::create($data);
    }

    public function updateStudio(Studio $studio, array $data): Studio
    {
        $studio->update($data);

        return $studio;
    }

    // Archive instead of hard-deleting, so historical bookings remain intact.
    public function archiveStudio(Studio $studio): void
    {
        $studio->update(['status' => 'inactive']);
    }

    /**
     * Upload and store gallery images for a studio, enforcing the
     * per-studio image limit. The very first image ever uploaded
     * automatically becomes the primary image.
     *
     * @param array<\Illuminate\Http\UploadedFile> $files
     */
    public function addImages(Studio $studio, array $files): void
    {
        $existingCount = $studio->images()->count();
        $hasPrimary = $studio->images()->where('is_primary', true)->exists();
        $position = (int) $studio->images()->max('position');

        foreach ($files as $file) {
            if ($existingCount >= self::MAX_IMAGES) {
                break;
            }

            $position++;
            $existingCount++;

            StudioImage::create([
                'studio_id'  => $studio->studio_id,
                'image_path' => $this->uploadAndCompress($file),
                'position'   => $position,
                'is_primary' => !$hasPrimary,
            ]);

            $hasPrimary = true;
        }
    }

    public function deleteImage(StudioImage $image): void
    {
        $studioId = $image->studio_id;
        $wasPrimary = $image->is_primary;

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        if ($wasPrimary) {
            $next = StudioImage::where('studio_id', $studioId)->orderBy('position')->first();
            $next?->update(['is_primary' => true]);
        }
    }

    public function setPrimaryImage(StudioImage $image): void
    {
        StudioImage::where('studio_id', $image->studio_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
    }

    /**
     * Persist a new ordering for a studio's gallery images.
     *
     * @param array<int> $orderedImageIds
     */
    public function reorderImages(Studio $studio, array $orderedImageIds): void
    {
        foreach (array_values($orderedImageIds) as $index => $imageId) {
            StudioImage::where('studio_id', $studio->studio_id)
                ->where('image_id', $imageId)
                ->update(['position' => $index + 1]);
        }
    }

    public function createUnavailability(Studio $studio, array $data): StudioUnavailability
    {
        return StudioUnavailability::create([
            'studio_id' => $studio->studio_id,
            'type'      => $data['type'],
            'start_at'  => $data['start_at'],
            'end_at'    => $data['end_at'],
            'reason'    => $data['reason'] ?? null,
            'staff_id'  => $data['staff_id'] ?? null,
        ]);
    }

    public function deleteUnavailability(StudioUnavailability $period): void
    {
        $period->delete();
    }

    /**
     * Build a combined list of calendar events (bookings + maintenance/blocked
     * periods) for a studio within the given date range.
     *
     * @return array<int, array{id: string, type: string, title: string, start: string, end: string, color: string}>
     */
    public function getCalendarEvents(Studio $studio, Carbon $start, Carbon $end): array
    {
        $events = [];

        $bookings = Booking::where('studio_id', $studio->studio_id)
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('booking_status', ['confirmed', 'completed', 'pending'])
            ->get();

        foreach ($bookings as $booking) {
            $isPending = $booking->booking_status === 'pending';

            $events[] = [
                'id'    => 'booking-' . $booking->booking_id,
                'type'  => 'booking',
                'title' => $isPending ? 'Pending Booking' : 'Confirmed Booking',
                'start' => $booking->booking_date . ' ' . $booking->start_time,
                'end'   => $booking->booking_date . ' ' . $booking->end_time,
                'color' => $isPending ? '#F0AD4E' : '#3B82C4',
            ];
        }

        $periods = StudioUnavailability::where('studio_id', $studio->studio_id)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->get();

        foreach ($periods as $period) {
            $isMaintenance = $period->type === 'maintenance';

            $events[] = [
                'id'    => 'unavailability-' . $period->unavailability_id,
                'type'  => $period->type,
                'title' => $isMaintenance ? 'Maintenance' : 'Blocked',
                'start' => $period->start_at->format('Y-m-d H:i:s'),
                'end'   => $period->end_at->format('Y-m-d H:i:s'),
                'color' => $isMaintenance ? '#FB923C' : '#9AA0A6',
            ];
        }

        return $events;
    }

    /**
     * Find the next available booking slot for a studio, starting from
     * the given date (inclusive).
     *
     * @return array{date: string, slot: array{start: string, end: string, label: string, status: string}}|null
     */
    public function getNextAvailableSlot(Studio $studio, Carbon $from): ?array
    {
        if ($studio->status !== 'available') {
            return null;
        }

        for ($i = 0; $i < self::NEXT_SLOT_SEARCH_DAYS; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $schedule = $this->bookingService->getAvailability($studio, $date);

            foreach ($schedule['slots'] as $slot) {
                if ($slot['status'] === 'available') {
                    return ['date' => $date, 'slot' => $slot];
                }
            }
        }

        return null;
    }

    private function uploadAndCompress($file): string
    {
        $filename = time() . '_' . uniqid() . '.jpg';
        $folder = storage_path('app/public/studios');

        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        if (extension_loaded('gd')) {
            $savePath = $folder . '/' . $filename;
            $imageInfo = getimagesize($file->getRealPath());
            $mime = $imageInfo['mime'];

            if ($mime === 'image/jpeg') {
                $source = imagecreatefromjpeg($file->getRealPath());
            } elseif ($mime === 'image/png') {
                $source = imagecreatefrompng($file->getRealPath());
            } elseif ($mime === 'image/webp') {
                $source = imagecreatefromwebp($file->getRealPath());
            } else {
                return $file->store('studios', 'public');
            }

            $origWidth = imagesx($source);
            $origHeight = imagesy($source);
            $maxWidth = 1200;
            $maxHeight = 1200;

            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
            $newWidth = (int) ($origWidth * $ratio);
            $newHeight = (int) ($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($mime === 'image/png' || $mime === 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagejpeg($resized, $savePath, 80);
            imagedestroy($source);
            imagedestroy($resized);

            return 'studios/' . $filename;
        }

        return $file->store('studios', 'public');
    }
}
