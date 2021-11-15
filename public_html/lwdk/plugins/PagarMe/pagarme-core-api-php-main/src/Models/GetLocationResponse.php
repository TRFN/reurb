<?php
/*
 * PagarmeCoreApiLib
 *
 * This file was automatically generated by APIMATIC v2.0 ( https://apimatic.io ).
 */

namespace PagarmeCoreApiLib\Models;

use JsonSerializable;

/**
 *Response object for geetting an order location request
 */
class GetLocationResponse implements JsonSerializable
{
    /**
     * Latitude
     * @required
     * @var string $latitude public property
     */
    public $latitude;

    /**
     * Longitude
     * @required
     * @var string $longitude public property
     */
    public $longitude;

    /**
     * Constructor to set initial or default values of member properties
     * @param string $latitude  Initialization value for $this->latitude
     * @param string $longitude Initialization value for $this->longitude
     */
    public function __construct()
    {
        if (2 == func_num_args()) {
            $this->latitude  = func_get_arg(0);
            $this->longitude = func_get_arg(1);
        }
    }


    /**
     * Encode this object to JSON
     */
    public function jsonSerialize()
    {
        $json = array();
        $json['latitude']  = $this->latitude;
        $json['longitude'] = $this->longitude;

        return $json;
    }
}
