<?php

/**
 * File containing the Field Criterion parser class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\REST\Server\Input\Parser\Criterion;

use eZ\Publish\Core\REST\Common\Input\BaseParser;
use eZ\Publish\Core\REST\Common\Input\ParsingDispatcher;
use eZ\Publish\Core\REST\Common\Exceptions;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field as FieldCriterion;

/**
 * Parser for Field Criterion.
 */
class Field extends BaseParser
{
    /**
     * Parses input structure to a Criterion object.
     *
     * @param array $data
     * @param \eZ\Publish\Core\REST\Common\Input\ParsingDispatcher $parsingDispatcher
     *
     * @throws \eZ\Publish\Core\REST\Common\Exceptions\Parser
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field
     */
    public function parse(array $data, ParsingDispatcher $parsingDispatcher)
    {
        if (!isset($data['FieldCriterion'])) {
            throw new Exceptions\Parser('Invalid <FieldCriterion> format');
        }

        if (!isset($data['FieldCriterion']['Target'])) {
            throw new Exceptions\Parser('Invalid <Target> format');
        }

        $target = $data['FieldCriterion']['Target'];

        if (!isset($data['FieldCriterion']['Value'])) {
            throw new Exceptions\Parser('Invalid <Value> format');
        }

        if (!in_array(gettype($data['FieldCriterion']['Value']), ['integer', 'string', 'array'])) {
            throw new Exceptions\Parser('Invalid <Value> format');
        }

        $value = is_array($data['FieldCriterion']['Value'])
            ? $data['FieldCriterion']['Value']
            : explode(',', $data['FieldCriterion']['Value']);

        return new FieldCriterion($target, null, $value);
    }
}
