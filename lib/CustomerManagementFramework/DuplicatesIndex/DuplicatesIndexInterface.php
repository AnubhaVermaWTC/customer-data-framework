<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 2017-03-03
 * Time: 09:55
 */

namespace CustomerManagementFramework\DuplicatesIndex;

use CustomerManagementFramework\Model\CustomerInterface;
use Psr\Log\LoggerInterface;

interface DuplicatesIndexInterface
{
    public function recreateIndex(LoggerInterface $logger);

    public function updateDuplicateIndexForCustomer(CustomerInterface $customer);

    public function calculatePotentialDuplicates();

    public function setAnalyzeFalsePositives($analyzeFalsePositives);
    public function getAnalyzeFalsePositives();
}