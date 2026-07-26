<?php

namespace Ubermuda\FeatureFlagsBundle\Enum;

enum FeatureFlagType: string
{
    case Bool = 'bool';
    case Int = 'int';
    case String = 'string';
    case Select = 'select';
}
