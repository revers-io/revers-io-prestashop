<?php
namespace ReversIO\Services\Cache;

use ReversIO\Services\APIConnect\ReversIOApi;

class Cache
{
    /** List of product from Revers.io */
    private $listModels = null;

    private $listBrands;

    /** @var \ReversIO */
    private $module;

    public function __construct(\ReversIO $module)
    {
        $this->module = $module;
    }

    public function getListModels()
    {
        if (null === $this->listModels) {
            $this->updateModelList();
        }

        return $this->listModels;
    }

    public function updateModelList()
    {
        /** @var ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->module->getContainer()->get('reversIoApiConnect');
        $this->listModels = $reversIOAPIConnect->getListModels();
    }

    public function getBrands()
    {
        if (null === $this->listBrands) {
            $this->updateBrandsList();
        }

        return $this->listBrands;
    }

    public function updateBrandsList()
    {
        /** @var ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->module->getContainer()->get('reversIoApiConnect');
        $this->listBrands = $reversIOAPIConnect->getListBrands();
    }
}
