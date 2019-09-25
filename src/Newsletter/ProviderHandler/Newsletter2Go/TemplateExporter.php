<?php
/**
 * Created by PhpStorm.
 * User: tmittendorfer
 * Date: 13.07.2018
 * Time: 09:34
 */

namespace CustomerManagementFrameworkBundle\Newsletter\ProviderHandler\Newsletter2Go;


use CustomerManagementFrameworkBundle\Newsletter\ProviderHandler\NewsletterTemplateExporterInterface;
use Pimcore\Model\Document;



class TemplateExporter implements NewsletterTemplateExporterInterface
{

    protected $newsletter2GoRESTApi;

    public function __construct(\NL2GO\Newsletter2Go_REST_Api $newsletter2GoRESTApi, $listId)
    {
        $this->newsletter2GoRESTApi = $newsletter2GoRESTApi;
        $this->listId = $listId;
    }


    /**
     * for placeholders refer to: https://hilfe.newsletter2go.com/newsletter-erstellen/personalisierung/wie-kann-ich-merkmale-uber-platzhalter-im-newsletter-ausgeben-und-individuell-anpassen.html
     *
     *
     * @param Document\Newsletter $document
     * @throws \Exception
     */
    public function exportTemplate(Document $document)
    {
        $html = \Pimcore\Model\Document\Service::render($document);
        //prevent nl2go placeholders to be encoded and get applied with the domain
        $html = str_replace('href="{{ ', 'data-save-my-link="{{ ', $html);


        // modifying the content e.g set absolute urls...
        $html = \Pimcore\Helper\Mail::embedAndModifyCss($html, $document);
        $html = \Pimcore\Helper\Mail::setAbsolutePaths($html, $document);

        //prevent nl2go placeholders to be encoded and get applied with the domain
        $html = str_replace('data-save-my-link="{{ ', 'href="{{ ', $html);

        $response = $this->newsletter2GoRESTApi->createNewsletter($this->listId, 'default', $document->getKey(), $document->getFrom(), $document->getSubject(), $html);
    }
}