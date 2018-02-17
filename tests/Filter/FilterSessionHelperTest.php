<?php

namespace Tests\CubeTools\CubeCommonBundle\Filter;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\AbstractType as DummyFilterType;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use CubeTools\CubeCommonBundle\Filter\FilterSessionHelper;

class FilterSessionHelperTest extends FormIntegrationTestCase // this class has $this->form
{
    /**
     * no callback function.
     */
    const NO_ON_SUCCESS_KEEP_FN = null;

    /**
     * Tests getFilterDataFromSession and setFilterDataToSession.
     */
    public function testSessionGetSet()
    {
        $mSess = new Session(new MockArraySessionStorage());
        $pageName = 'pageName_gfs';

        $this->assertNull(FilterSessionHelper::getFilterDataFromSession($mSess, $pageName));
        $filter = array('g' => 'G', 'h' => 'H');
        $tFilter = $filter;
        FilterSessionHelper::setFilterDataToSession($mSess, $pageName, $filter, static::NO_ON_SUCCESS_KEEP_FN);
        $this->assertSame($filter, $tFilter);
        $this->assertSame($filter, FilterSessionHelper::getFilterDataFromSession($mSess, $pageName));

        return array('filter' => $filter, 'mSess' => $mSess, 'pageName' => $pageName);
    }

    /**
     * Tests if callback of setFilterDataToSession is called in the correct places.
     *
     * @depends testSessionGetSet
     */
    public function testSessionSetCallback(array $dep1)
    {
        $filter = $dep1['filter'];
        $mSess = $dep1['mSess'];
        $pageName = $dep1['pageName'];

        FilterSessionHelper::setFilterDataToSession($mSess, $pageName, $filter, array($this, 'invalid_cbk'));
        $this->assertTrue(true, 'callback not called on same data');

        $mockCbk = $this->getMockBuilder(stdClass::class)->setMethods(array('cbk'))->getMock();
        $mockCbk->expects($this->once())->method('cbk')->with(
            $this->equalTo($mSess),
            $this->contains($pageName.'_filter')
        );
        $filter['g'] = 'gg';
        FilterSessionHelper::setFilterDataToSession($mSess, $pageName, $filter, array($mockCbk, 'cbk'));
    }

    public function testGetFilterDataReset()
    {
        $thisUrl = '/dummy/uri/for/reset';
        $mSes = new Session(new MockArraySessionStorage());
        $mReq = Request::create($thisUrl, 'GET', array('filter_reset' => 1));
        $mReq->setSession($mSes);

        $type = $this->getMockBuilder(DummyFilterType::class)->setMethods(null)->getMock();
        $form = $this->factory->create(get_class($type));

        $d = FilterSessionHelper::getFilterData($mReq, $form, 'pageName_gfdr', static::NO_ON_SUCCESS_KEEP_FN);
        $this->assertSame($thisUrl, $d['redirect']);

        $mReq2 = Request::create($thisUrl, 'GET', array('filter_reset' => 1, 'bla' => 'hi'));
        $mReq2->setSession($mSes);
        $d2 = FilterSessionHelper::getFilterData($mReq2, $form, 'pageName_gfdr', static::NO_ON_SUCCESS_KEEP_FN);
        $this->assertSame($thisUrl.'?bla=hi', $d2['redirect']);
    }

    public function testGetFilterData()
    {
        $mSes = new Session(new MockArraySessionStorage());
        $mReq = Request::create('/dummy/uri/');
        $mReq->setSession($mSes);

        $type = $this->getMockBuilder(DummyFilterType::class)->setMethods(null)->getMock();
        $bldr = $this->factory->createBuilder(get_class($type));
        $bldr->add('someChild');
        $bldr->setRequestHandler(new HttpFoundationRequestHandler());
        $form = $bldr->getForm();

        $d = FilterSessionHelper::getFilterData($mReq, $form, 'pageName_gfd', static::NO_ON_SUCCESS_KEEP_FN);

        $d1 = $d;
        unset($d1['filter']);
        $this->assertEquals(array('page' => 1, 'redirect' => null, 'options' => array()), $d1);
        $this->assertCount(0, $d['filter']);

        $this->markTestIncomplete('TODO: test with data');
    }
}
