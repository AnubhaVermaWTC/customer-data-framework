<?php

use CustomerManagementFramework\Controller\Admin;
use CustomerManagementFramework\Controller\Traits\PaginatorController;
use CustomerManagementFramework\Factory;
use CustomerManagementFramework\Listing\Filter;
use CustomerManagementFramework\Listing\FilterHandler;
use CustomerManagementFramework\Model\CustomerSegmentInterface;
use CustomerManagementFramework\Plugin;
use Pimcore\Model\Object\Customer;
use Pimcore\Model\Object\CustomerSegment;

class CustomerManagementFramework_CustomersController extends Admin
{
    use PaginatorController;

    public function listAction()
    {
        $this->enableLayout();

        $this->loadSegmentGroup('gender');

        $filters   = $this->fetchListFilters();
        $listing   = $this->buildListing($filters);
        $paginator = $this->buildPaginator($listing);

        $this->view->paginator = $paginator;
    }

    public function exportAction()
    {
        $this->disableLayout();
        $this->disableViewAutoRender();

        $exporterName    = $this->getParam('exporter', 'csv');
        $exporterManager = Factory::getInstance()->getCustomerListExporterManager();

        if (!$exporterManager->hasExporter($exporterName)) {
            throw new InvalidArgumentException('Exporter does not exist');
        }

        $filters  = $this->fetchListFilters();
        $listing  = $this->buildListing($filters);
        $exporter = $exporterManager->buildExporter($exporterName, $listing);

        $filename = sprintf(
            '%s-%s-segment-export.csv',
            $exporterName,
            \Carbon\Carbon::now()->format('YmdHis')
        );

        /** @var Zend_Controller_Response_Http $response */
        $response = $this->getResponse();
        $response
            ->setHeader('Content-Type', $exporter->getMimeType())
            ->setHeader('Content-Length', $exporter->getFilesize())
            ->setHeader('Content-Disposition', sprintf('attachment; filename="%s"', $filename))
            ->setBody($exporter->getExportData());
    }

    /**
     * Load a segment group and set it on the view
     *
     * @param $name
     */
    protected function loadSegmentGroup($name)
    {
        /** @var \Pimcore\Model\Object\CustomerSegmentGroup $group */
        $segmentGroup = \Pimcore\Model\Object\CustomerSegmentGroup::getByName($name, 1);
        if (!$segmentGroup) {
            throw new InvalidArgumentException(sprintf('Segment group %s was not found', $name));
        }

        if (!isset($this->view->segmentGroups)) {
            $this->view->segmentGroups = [];
        }

        $this->view->segmentGroups[] = $segmentGroup;
    }

    /**
     * @param array $filters
     * @return Customer\Listing
     */
    protected function buildListing(array $filters = [])
    {
        $listing = new Customer\Listing();
        $listing
            ->setOrderKey('o_id')
            ->setOrder('ASC');

        $this->addListingFilters($listing, $filters);

        return $listing;
    }

    /**
     * @param Customer\Listing $listing
     * @param array $filters
     */
    protected function addListingFilters(Customer\Listing $listing, array $filters = [])
    {
        $handler = new FilterHandler($listing);

        $filterProperties = Plugin::getConfig()->CustomerList->filterProperties;

        $equalsProperties = isset($filterProperties->equals) ? $filterProperties->equals->toArray() : [];
        $searchProperties = isset($filterProperties->search) ? $filterProperties->search->toArray() : [];

        foreach ($equalsProperties as $property => $databaseField) {
            if (array_key_exists($property, $filters)) {
                $handler->addFilter(new Filter\Equals($databaseField, $filters[$property]));
            }
        }

        foreach ($searchProperties as $property => $databaseField) {
            if (array_key_exists($property, $filters)) {
                $handler->addFilter(new Filter\Search($databaseField, $filters[$property]));
            }
        }

        $prefilteredSegment = $this->fetchPrefilteredSegment();
        if (null !== $prefilteredSegment) {
            $handler->addFilter(new Filter\CustomerSegment($prefilteredSegment->getGroup(), [$prefilteredSegment]));

            $this->view->prefilteredSegment = $prefilteredSegment;
        }

        if (array_key_exists('segments', $filters)) {
            foreach ($filters['segments'] as $groupId => $segmentIds) {
                // prefiltered segment can't be overwritten
                if (null !== $prefilteredSegment && $prefilteredSegment->getGroup()->getId() === $groupId) {
                    continue;
                }

                /** @var \Pimcore\Model\Object\CustomerSegmentGroup $segmentGroup */
                $segmentGroup = \Pimcore\Model\Object\CustomerSegmentGroup::getById($groupId);
                if (!$segmentGroup) {
                    throw new InvalidArgumentException(sprintf('Segment group %d was not found', $groupId));
                }

                $segments = [];
                foreach ($segmentIds as $prefilteredSegmentId) {
                    $segment = CustomerSegment::getById($prefilteredSegmentId);

                    if (!$segment) {
                        throw new InvalidArgumentException(sprintf('Segment %d was not found', $prefilteredSegmentId));
                    }

                    $segments[] = $segment;
                }

                $handler->addFilter(new Filter\CustomerSegment($segmentGroup, $segments));
            }
        }
    }

    /**
     * @return CustomerSegmentInterface|null
     */
    protected function fetchPrefilteredSegment()
    {
        $segmentId = $this->getParam('segmentId');

        if ($segmentId) {
            $segment = CustomerSegment::getById($segmentId);
            if (!$segment) {
                throw new InvalidArgumentException(sprintf('Segment %d was not found', $segmentId));
            }

            // params still needed when clearing all filters
            $clearUrlParams = $this->view->clearUrlParams ?: [];
            $clearUrlParams['segmentId'] = $segment->getId();

            $this->view->prefilteredSegment = $segment;
            $this->view->clearUrlParams     = $clearUrlParams;

            return $segment;
        }
    }

    /**
     * Fetch filters and set them on view
     *
     * @return array
     */
    protected function fetchListFilters()
    {
        /** @var \Zend_Controller_Action $this */
        $filters = $this->getParam('filter', []);
        $this->view->filters = $filters;

        return $filters;
    }
}
