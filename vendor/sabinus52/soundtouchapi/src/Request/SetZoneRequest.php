<?php
/**
 * Requête de la zone MultiRoom de l'enceinte
 *
 * @author Olivier <sabinus52@gmail.com>
 *
 * @package SoundTouchApi
 */

namespace Sabinus\SoundTouch\Request;

use Sabinus\SoundTouch\ClientApi;
use Sabinus\SoundTouch\Component\Zone;


class SetZoneRequest extends RequestAbstract implements RequestInterface
{

    /**
     * @var Zone
     */
    private $zone;

    
    /**
     * @see RequestAbstract::__construct
     */
    public function __construct($refresh = false)
    {
        parent::__construct(ClientApi::METHOD_POST, 'setZone', $refresh);
    }

    /**
     * @see RequestInterface
     */
    public function getPayload()
    {
        $result = '<zone master="' . $this->zone->getMaster() . '" senderIPAddress="' . $this->zone->getSender() . '">';
        foreach ($this->zone->getSlaves() as $slave) {
            $result.= '<member ipaddress="' . $slave->getIpAddress() . '">' . $slave->getMacAddress() . '</member>';
        }
        $result.= '</zone>';
        return $result;
    }

    /**
     * @see RequestInterface
     */
    public function createClass()
    {
        return null;
    }


    /**
     * Affecte une nouvelle zone
     * 
     * @param Zone $zone
     * @return SetZoneRequest
     */
    public function setZone(Zone $zone)
    {
        $this->zone = $zone;
        return $this;
    }

}
