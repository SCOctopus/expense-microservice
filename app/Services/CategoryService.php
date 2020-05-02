<?php


namespace App\Services;


use App\Repositories\CategoryRepository;

class CategoryService
{
    public function __construct(
        CategoryRepository $categoryRepository
    )
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function listAll()
    {
        return $this->categoryRepository->listAll();
    }
}
