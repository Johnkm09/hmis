<?php

namespace App\Services;

use App\Models\RoomType\RoomType;
use App\Models\RoomType\RoomTypeImage;
use App\Repositories\RoomTypeImage\RoomTypeImageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RoomTypeImageService
{
    protected RoomTypeImageInterface $roomTypeImageRepository;

    public function __construct(RoomTypeImageInterface $roomTypeImageRepository)
    {
        $this->roomTypeImageRepository = $roomTypeImageRepository;
    }

    public function getByRoomType(int $roomTypeId)
    {
        return $this->roomTypeImageRepository->getByRoomType($roomTypeId);
    }

    public function add(RoomType $roomType, UploadedFile $file)
    {
        return DB::transaction(function () use ($roomType, $file) {
            $imageCount = $roomType->images()->count();

            if ($imageCount >= 8) {
                throw ValidationException::withMessages([
                    'images' => ['This image has already been uploaded.'],
                ]);
            }

            $hash = hash_file('sha256', $file->getRealPath());

            $duplicate = $roomType->images()
                ->where('hash', $hash)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'images' => ['This image has already been uploaded.'],
                ]);
            }
            $isPrimary = $imageCount === 0;

            $path = $file->store(
                "room-types/{$roomType->id}",
                'public'
            );

            return $this->roomTypeImageRepository->create([
                'room_type_id' => $roomType->id,
                'path' => $path,
                'hash' => $hash,
                'is_primary' => $isPrimary,
            ]);
        });
    }

    public function setPrimary(RoomTypeImage $image)
    {
        return DB::transaction(function () use ($image) {
            $image->roomType
                ->images()
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);

            return $this->roomTypeImageRepository->update(
                $image->id,
                ['is_primary' => true]
            );
        });
    }

    public function delete(RoomTypeImage $image): void
    {
        DB::transaction(function () use ($image) {
            Storage::disk('public')->delete($image->path);

            $roomType = $image->roomType;
            $wasPrimary = $image->is_primary;

            $this->roomTypeImageRepository->delete($image->id);

            if ($wasPrimary) {
                $replacement = $roomType->images()->oldest()->first();

                if ($replacement) {
                    $this->roomTypeImageRepository->update(
                        $replacement->id,
                        ['is_primary' => true]
                    );
                }
            }
        });
    }
}
