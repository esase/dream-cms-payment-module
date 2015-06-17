<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class PaymentItemLink extends AbstractHelper
{
   /**
     * Payment item status
     *
     * @param array $info
     *      string page_name
     *      sting slug
     *      string title
     * @return string
     */
    public function __invoke($info)
    {
        // get page url
        $pageUrl = $this->getView()->
                pageUrl($info['page_name'], [], null, false, $info['slug']);

        if (false !== $pageUrl) {
            return '<a target="_blank" href="' . $this->getView()->url('page', ['page_name' =>
                    $pageUrl, 'slug' => $info['slug']], ['force_canonical' => true]) . '">' . $info['title'] . '</a>';
        }

        return $info['title'];
    }
}