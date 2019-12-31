<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\View;

use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Resource;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\Http\Response;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\ViewEvent;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @subpackage UnitTest
 */
class HalJsonStrategyTest extends TestCase
{
    public function setUp()
    {
        $this->response = new Response;
        $this->event    = new ViewEvent;
        $this->event->setResponse($this->response);

        $this->renderer = new HalJsonRenderer(new ApiProblemRenderer());
        $this->strategy = new HalJsonStrategy($this->renderer);
    }

    public function testSelectRendererReturnsNullIfModelIsNotAHalJsonModel()
    {
        $this->assertNull($this->strategy->selectRenderer($this->event));
    }

    public function testSelectRendererReturnsRendererIfModelIsAHalJsonModel()
    {
        $model = new HalJsonModel();
        $this->event->setModel($model);
        $this->assertSame($this->renderer, $this->strategy->selectRenderer($this->event));
    }

    public function testInjectResponseDoesNotSetContentTypeHeaderIfRendererDoesNotMatch()
    {
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        $this->assertFalse($headers->has('Content-Type'));
    }

    public function testInjectResponseDoesNotSetContentTypeHeaderIfResultIsNotString()
    {
        $this->event->setRenderer($this->renderer);
        $this->event->setResult(array('foo'));
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        $this->assertFalse($headers->has('Content-Type'));
    }

    public function testInjectResponseSetsContentTypeHeaderToDefaultIfNotHalModel()
    {
        $this->event->setRenderer($this->renderer);
        $this->event->setResult('{"foo":"bar"}');
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        $this->assertTrue($headers->has('Content-Type'));
        $header = $headers->get('Content-Type');
        $this->assertEquals('application/json', $header->getFieldValue());
    }

    public function halObjects()
    {
        $resource = new Resource(array(
            'foo' => 'bar',
        ), 'identifier', 'route');
        $link = new Link('self');
        $link->setRoute('resource/route')->setRouteParams(array('id' => 'identifier'));
        $resource->getLinks()->add($link);

        $collection = new Collection(array($resource));
        $collection->setCollectionRoute('collection/route');
        $collection->setResourceRoute('resource/route');

        return array(
            'resource'   => array($resource),
            'collection' => array($collection),
        );
    }

    /**
     * @dataProvider halObjects
     */
    public function testInjectResponseSetsContentTypeHeaderToHalForHalModel($hal)
    {
        $model = new HalJsonModel(array('payload' => $hal));

        $this->event->setModel($model);
        $this->event->setRenderer($this->renderer);
        $this->event->setResult('{"foo":"bar"}');
        $this->strategy->injectResponse($this->event);
        $headers = $this->response->getHeaders();
        $this->assertTrue($headers->has('Content-Type'));
        $header = $headers->get('Content-Type');
        $this->assertEquals('application/hal+json', $header->getFieldValue());
    }
}