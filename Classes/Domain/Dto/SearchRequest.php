<?php

namespace Nordkirche\NkcAddress\Domain\Dto;

class SearchRequest
{
    /**
     * @var string
     */
    protected $search = '';

    /**
     * @var string
     */
    protected $city = '';

    /**
     * @var int
     */
    protected $category;

    /**
     * @var array
     */
    protected $array = [];

    /**
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param string $search
     */
    public function setSearch(string $search)
    {
        $this->search = $search;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city)
    {
        $this->city = $city;
    }

    /**
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param int $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = [];
        if ($this->getSearch() != '') {
            $array['search'] = $this->getSearch();
        }
        if ($this->getCity() != '') {
            $array['city'] = $this->getCity();
        }
        if ($this->getCategory() != 0) {
            $array['category'] = $this->getCategory();
        }
        return $array;
    }

    /**
     * @return array
     */
    public function getArray(): array
    {
        return $this->toArray();
    }

    /**
     * @param array|null $data
     */
    public function decorate($data): void
    {
        if (is_array($data)) {
            foreach (['search', 'category', 'city'] as $property) {
                if (!empty($data[$property])) {
                    $setterName = 'set' . ucfirst($property);
                    $this->$setterName($data[$property]);
                }
            }
        }
    }
}
