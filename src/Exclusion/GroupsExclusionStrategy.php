<?php

declare(strict_types=1);

namespace JMS\Serializer\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\ClassMetadataInterface;
use JMS\Serializer\Metadata\PropertyMetadataInterface;

final class GroupsExclusionStrategy implements ExclusionStrategyInterface
{
    const DEFAULT_GROUP = 'Default';

    private $groups = [];
    private $nestedGroups = false;

    public function __construct(array $groups)
    {
        if (empty($groups)) {
            $groups = [self::DEFAULT_GROUP];
        }

        foreach ($groups as $group) {
            if (is_array($group)) {
                $this->nestedGroups = true;
                break;
            }
        }

        if ($this->nestedGroups) {
            $this->groups = $groups;
        } else {
            foreach ($groups as $group) {
                $this->groups[$group] = true;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadataInterface $metadata, Context $navigatorContext): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadataInterface $property, Context $navigatorContext): bool
    {
        if ($this->nestedGroups) {
            $groups = $this->getGroupsFor($navigatorContext);

            if (!$property->getGroups()) {
                return !in_array(self::DEFAULT_GROUP, $groups);
            }

            return $this->shouldSkipUsingGroups($property, $groups);
        } else {

            if (!$property->getGroups()) {
                return !isset($this->groups[self::DEFAULT_GROUP]);
            }

            foreach ($property->getGroups() as $group) {
                if (isset($this->groups[$group])) {
                    return false;
                }
            }
            return true;
        }
    }

    private function shouldSkipUsingGroups(PropertyMetadataInterface $property, $groups)
    {
        foreach ($property->getGroups() as $group) {
            if (in_array($group, $groups)) {
                return false;
            }
        }

        return true;
    }

    private function getGroupsFor(Context $navigatorContext)
    {
        $paths = $navigatorContext->getCurrentPath();

        $groups = $this->groups;
        foreach ($paths as $index => $path) {
            if (!array_key_exists($path, $groups)) {
                break;
            }

            if (!is_array($groups[$path])) {
                throw new RuntimeException(sprintf('The group value for the property path "%s" should be an array, "%s" given', $index, gettype($groups[$path])));
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
