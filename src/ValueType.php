<?php

namespace Zendrop\Data;

enum ValueType: string
{
    case BOOL = 'bool';
    case INT = 'int';
    case FLOAT = 'float';
    case STRING = 'string';
    case NULL = 'null';
    case ARRAY = 'array';
    case OBJECT = 'object';
    case ENUM = 'enum';
    case MIXED = 'mixed';
}
