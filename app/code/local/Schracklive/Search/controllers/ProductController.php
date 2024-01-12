<?php

class Schracklive_Search_ProductController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        $this->jsonAction();
    }

    public function jsonAction() {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        /** @var Schracklive_Search_Model_Search $searchModel */
        $searchModel = Mage::getModel('search/search');
        $searchModel->setSort('position_intS');
        $searchModel->setSortOrder('asc');
        $query = $this->getRequest()->get('query');
        if ($query !== null) {
            $searchModel->setQuery($query);
        }
        $start = $this->getRequest()->get('start');
        if ($start !== null) {
            $searchModel->setStart((int)$start);
        }
        $limit = $this->getRequest()->get('limit');
        if ($limit !== null) {
            $searchModel->setLimit((int)$limit);
        }
        $category = $this->getRequest()->get('category');
        if ($category !== null) {
            $searchModel->setCategory((int)$category);
        }
        $facets = $this->getRequest()->get('facets');
        if ($facets !== null) {
            $searchModel->setFacets($facets);
        }
        $highAvailability = $this->getRequest()->get('high_availability');
        if ($highAvailability !== null) {
            $searchModel->setHighAvailability((bool)$highAvailability);
        }
        $products = $searchModel->getProducts();
        $this->getResponse()->setBody(json_encode($products));
    }

    public function searchAction() {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        /** @var Schracklive_Search_Model_Search $searchModel */
        $searchModel = Mage::getModel('search/search');
        $query = $this->getRequest()->get('query');
        if ($query !== null) {
            $searchModel->setQuery($query);
        }
        $start = $this->getRequest()->get('start');
        if ($start !== null) {
            $searchModel->setStart((int)$start);
        }
        $limit = $this->getRequest()->get('limit');
        if ($limit !== null) {
            $searchModel->setLimit((int)$limit);
        }
        $saleLimit = $this->getRequest()->get('saleLimit');
        if ($saleLimit !== null) {
            $searchModel->setSaleLimit((int)$saleLimit);
        }
        $pageLimit = $this->getRequest()->get('pageLimit');
        if ($pageLimit !== null) {
            $searchModel->setPagesLimit((int)$pageLimit);
        }
        $category = $this->getRequest()->get('category');
        if ($category !== null) {
            $searchModel->setCategory((int)$category);
        }
        $facets = $this->getRequest()->get('facets');
        if ($facets !== null) {
            $searchModel->setFacets($facets);
        }
        $sort = $this->getRequest()->get('sort');
        if ($sort !== null) {
            $searchModel->setSort($sort);
        }
        $sortOrder = $this->getRequest()->get('sort_order');
        if ($sortOrder !== null) {
            $searchModel->setSortOrder($sortOrder);
        }
        $products = $searchModel->getResults();
        $this->getResponse()->setBody(json_encode($products));
    }

    public function skusAction() {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        /** @var Schracklive_Search_Model_Search $searchModel */
        $searchModel = Mage::getModel('search/search');
        $query = $this->getRequest()->get('query');
        if ($query) {
            $searchModel->setQuery($query);
            $products = $searchModel->getSkus();
        } else {
            $products = [
                'status' => [
                    'error' => true,
                    'count' => 0
                ]
            ];
        }
        $this->getResponse()->setBody(json_encode($products));
    }
}
