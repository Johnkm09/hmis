<?php

namespace App\Services;

use App\Repositories\RoomType\RoomTypeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoomTypeService
{
    protected RoomTypeInterface $roomTypeRepository;
    protected RoomTypeImageService $roomTypeImageService;

    public function __construct(
        RoomTypeInterface $roomTypeRepository,
        RoomTypeImageService $roomTypeImageService
    ) {
        $this->roomTypeRepository = $roomTypeRepository;
        $this->roomTypeImageService = $roomTypeImageService;
    }

    // Returning all room types
    public function getAll()
    {
        return $this->roomTypeRepository->getAll();
    }

    // Creating room types
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $images = $data['images'];
            unset($data['images']);

            $data['slug'] = Str::slug($data['name']);

            $roomType = $this->roomTypeRepository->create($data);

            foreach ($images as $image) {
                $this->roomTypeImageService->add($roomType, $image);
            }

            return $roomType->load('images');
        });
    }

    // Return a room type by ID
    public function findById(int $id)
    {
        return $this->roomTypeRepository
            ->findById($id)
            ->load('images');
    }

    // Update room type
    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $images = $data['images'] ?? [];
            unset($data['images']);

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $roomType = $this->roomTypeRepository->update($id, $data);

            foreach ($images as $image) {
                $this->roomTypeImageService->add($roomType, $image);
            }

            return $roomType->load('images');
        });
    }

    // Delete room type
    public function delete(int $id): void
    {
        $this->roomTypeRepository->delete($id);
    }
}
