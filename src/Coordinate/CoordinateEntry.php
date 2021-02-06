<?php declare(strict_types=1);

namespace DCarbone\PHPConsulAPI\Coordinate;

/*
   Copyright 2016-2021 Daniel Carbone (daniel.p.carbone@gmail.com)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
 */

use DCarbone\PHPConsulAPI\AbstractModel;
use DCarbone\PHPConsulAPI\Hydration;

/**
 * Class CoordinateEntry
 */
class CoordinateEntry extends AbstractModel
{
    private const FIELD_COORDINATE = 'Coord';

    /** @var string */
    public string $Node = '';
    /** @var string */
    public string $Segment = '';
    /** @var \DCarbone\PHPConsulAPI\Coordinate\Coordinate|null */
    public ?Coordinate $Coord = null;

    /** @var array[] */
    protected static array $fields = [
        self::FIELD_COORDINATE => [
            Hydration::FIELD_TYPE  => Hydration::OBJECT,
            Hydration::FIELD_CLASS => Coordinate::class,
        ],
    ];

    /**
     * @return string
     */
    public function getNode(): string
    {
        return $this->Node;
    }

    /**
     * @return string
     */
    public function getSegment(): string
    {
        return $this->Segment;
    }

    /**
     * @return \DCarbone\PHPConsulAPI\Coordinate\Coordinate|null
     */
    public function getCoord(): ?Coordinate
    {
        return $this->Coord;
    }
}
