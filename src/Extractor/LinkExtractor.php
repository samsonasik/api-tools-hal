<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;

class LinkExtractor implements LinkExtractorInterface
{
    /**
     * @var ServerUrl
     */
    protected $serverUrlHelper;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var string
     */
    protected $serverUrlString;

    /**
     * @param  ServerUrl $serverUrlHelper
     * @param  Url $urlHelper
     */
    public function __construct(ServerUrl $serverUrlHelper, Url $urlHelper)
    {
        $this->serverUrlHelper = $serverUrlHelper;
        $this->urlHelper       = $urlHelper;
    }

    /**
     * @return string
     */
    protected function getServerUrl()
    {
        if ($this->serverUrlString === null) {
            $this->serverUrlString = call_user_func($this->serverUrlHelper);
        }
        return $this->serverUrlString;
    }

    /**
     * @inheritDoc
     */
    public function extract(Link $object)
    {
        if (!$object->isComplete()) {
            throw new DomainException(sprintf(
                'Link from resource provided to %s was incomplete; must contain a URL or a route',
                __METHOD__
            ));
        }

        $representation = $object->getProps();

        if ($object->hasUrl()) {
            $representation['href'] = $object->getUrl();

            return $representation;
        }

        $reuseMatchedParams = true;
        $options = $object->getRouteOptions();
        if (isset($options['reuse_matched_params'])) {
            $reuseMatchedParams = (bool) $options['reuse_matched_params'];
            unset($options['reuse_matched_params']);
        }

        $path = call_user_func(
            $this->urlHelper,
            $object->getRoute(),
            $object->getRouteParams(),
            $options,
            $reuseMatchedParams
        );

        if (substr($path, 0, 4) == 'http') {
            $representation['href'] = $path;
        } else {
            $representation['href'] = $this->getServerUrl() . $path;
        }

        return $representation;
    }
}
