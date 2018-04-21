<?php

namespace JMSGenerated;

final class JMSTest_Test__6d1e3b3bb4b303a72533514d782c0b437adbb584 implements \JMS\Serializer\Marshaller\MarshallerInterface
{
    public function __construct()
    {
    }
    public function __invoke(object $object, \JMS\Serializer\DeserializationContext $context) : string
    {
        if (true) {
            if (true) {
                \Closure::bind(function () {
                    return $this->foo;
                }, $object);
            }
        }
        if (true) {
            if (true) {
                \Closure::bind(function () {
                    return $this->bar;
                }, $object);
            }
        }
        if ($context->getDepth() <= 1) {
            if (true) {
                \Closure::bind(function () {
                    return $this->baz;
                }, $object);
            }
        }
        if (true) {
            if (count(array_intersect_key(array_flip($context->getGroups()), array(array('Blah' => 0)))) !== 0) {
                \Closure::bind(function () {
                    return $this->bax;
                }, $object);
            }
        }
    }
}