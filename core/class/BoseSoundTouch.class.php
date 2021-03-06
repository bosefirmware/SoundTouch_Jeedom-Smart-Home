<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/../config/SoundTouch.config.php';
use Sabinus\SoundTouch\JeedomSoundTouchApi;


class BoseSoundTouch extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */


    public static function deamon_info() {
		$return = array();
		$return['log'] = 'BoseSoundTouch';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('BoseSoundTouch', 'pull');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('BoseSoundTouch', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		$cron = cron::byClassAndFunction('BoseSoundTouch', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->halt();
    }

    public static function deamon_changeAutoMode($_mode) {
		$cron = cron::byClassAndFunction('BoseSoundTouch', 'pull');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron introuvable', __FILE__));
		}
		$cron->setEnable($_mode);
		$cron->save();
	}
    
    public static function pull($_eqLogic_id = null)
    {
        log::add('BoseSoundTouch', 'debug', "PULL ----------------------------");
		foreach (self::byType('BoseSoundTouch') as $eqLogic) {
            log::add('BoseSoundTouch', 'debug', "PULL : $_eqLogic_id - ".$eqLogic->getId());
			if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
				continue;
            }
            log::add('BoseSoundTouch', 'debug', "PULL : enable ".$eqLogic->getIsEnable());
			if ($eqLogic->getIsEnable() == 0) {
				continue;
            }

            $eqLogic->updateInfos();
            if ( intval(date('i')) == 52 && (intval(date('s')) >= 0 && intval(date('s') <= 10) )) {
                $eqLogic->updatePresets();
            }
        }
    }

    /**
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    /*public static function cron() {

        foreach (self::byType('BoseSoundTouch') as $equipment) {
            if ($equipment->getIsEnable() == 1) {
                $equipment->updateInfos();
            }
        }

    }*/
    


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
     */
    public static function cronDaily() {
        self::deamon_start();
    }
    



    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {

        $this->setCategory('multimedia', 1);
    
    }

    public function postSave() {

        $this->updateCommandSoundTouch();
        
    }

    public function preUpdate() {

        if ($this->getConfiguration('hostname') == '') {
            throw new Exception(__('Merci de renseigner l\'hôte ou l\'IP de l\'enceinte.',__FILE__));	
        }

    }

    public function postUpdate() {

        $this->updatePresets();

    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
     */
    public function toHtml($_version)
    {
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $_version = jeedom::versionAlias($_version);

        // Type du widget
        $typeWidget = $this->getConfiguration('format');

        // Traitement des infos
        foreach ($this->getCmd('info') as $info) {
            $value = $info->execCmd();
            $replace['#'.$info->getLogicalId().'_ID#'] = $info->getId();
            $replace['#'.$info->getLogicalId().'_VALUE#'] = $value;
            switch ($info->getLogicalId()) {
                case SoundTouchConfig::POWERED :
                    $replace['#'.$info->getLogicalId().'_VALUE#'] = ($value) ? 'power-on' : 'power-off';
                    break;
                case SoundTouchConfig::MUTED :
                    $replace['#'.$info->getLogicalId().'_VALUE#'] = ($value) ? 'mute-on' : 'mute';
                    break;
                case SoundTouchConfig::SOURCE :
                    $preview = $info->getConfiguration('preview');
                    $replace['#PREVIEW#'] = $info->getConfiguration('preview');
                    if ($value == 'UPDATE') {
                        $replace['#PREVIEW#'] = 'plugins/BoseSoundTouch/core/template/dashboard/images/loader.gif';
                    }
                    break;
                case SoundTouchConfig::TRACK_ARTIST :
                case SoundTouchConfig::TRACK_TITLE :
                    $replace['#'.$info->getLogicalId().'_VALUE#'] = ($value) ? $value : '&nbsp;';
                    break;
                case SoundTouchConfig::SHUFFLE :
                    $replace['#'.$info->getLogicalId().'_VALUE#'] = ($value) ? 'shuffle-on' : 'shuffle-off';
                    break;
                case SoundTouchConfig::REPEAT :
                    $replace['#'.$info->getLogicalId().'_VALUE#'] = 'repeat-'.strtolower($value);
                    break;
                case SoundTouchConfig::STATUS :
                    switch ($value) {
                        case 'PLAY'      : $replace['#PLAY_PAUSE_VALUE#'] = 'pause'; break;
                        case 'PAUSE'     : $replace['#PLAY_PAUSE_VALUE#'] = 'play'; break;
                        case 'STOP'      : $replace['#PLAY_PAUSE_VALUE#'] = 'play'; break;
                        case 'BUFFERING' : $replace['#PREVIEW#'] = 'plugins/BoseSoundTouch/core/template/dashboard/images/loader.gif';
                        default          : $replace['#PLAY_PAUSE_VALUE#'] = 'play'; break;
                    }
                    break;
            }
            log::add('BoseSoundTouch', 'debug', "HTML : #".$info->getLogicalId()."_VALUE#=".$replace['#'.$info->getLogicalId().'_VALUE#']);
        }


        // Traitement des commandes
        foreach ($this->getCmd('action') as $command) {
            $display = $command->getConfiguration('display');
            $replace['#'.$command->getLogicalId().'_ID#'] = $command->getId();

            switch ($command->getLogicalId()) {
                case SoundTouchConfig::PRESET_1 :
                case SoundTouchConfig::PRESET_2 :
                case SoundTouchConfig::PRESET_3 :
                case SoundTouchConfig::PRESET_4 :
                case SoundTouchConfig::PRESET_5 :
                case SoundTouchConfig::PRESET_6 :
                    $id = intval(substr($command->getLogicalId(), -1, 1));
                    $replace['#'.$command->getLogicalId().'_ICON#'] = 'plugins/BoseSoundTouch/core/template/dashboard/images/'.$display['icon'].'.png';
                    $preset = $command->getConfiguration('datas');
                    if (isset($preset['name'])) {
                        $replace['#'.$command->getLogicalId().'_NAME#'] = $preset['name'];
                        $replace['#'.$command->getLogicalId().'_ICON#'] = $preset['cache'];
                    }
                    break;
            }
        }

        return template_replace($replace, getTemplate('core', $_version, $typeWidget.'.eqLogic', 'BoseSoundTouch'));
    }
     

    /*
     * Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }
     */

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    }
     */

    /*     * **********************Getteur Setteur*************************** */

    /**
     * Rafraichissement des infos de l'enceinte
     */
    public function updateInfos()
    {
        // Paramètre de l'adresse de l'enceinte
        $hostname = $this->getConfiguration('hostname');
        $update = false;

        $speaker = new JeedomSoundTouchApi($hostname);
        log::add('BoseSoundTouch', 'debug', '=== REFRESH ==================================================');
        log::add('BoseSoundTouch', 'debug', "Rafraichissement des données depuis '$hostname'");

        // Récupération des différentes valeur
        if ($err = $speaker->getMessageError()) {
            log::add('BoseSoundTouch', 'error', 'Interrogation de l\'enceinte "'.$hostname.'" : '.$err);
        }
        $result = $speaker->isPowered();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::POWERED,  $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::POWERED.' = '.$result.' ('.$update.')');
        $result = $speaker->getCurrentSource();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::SOURCE, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::SOURCE.' = '.$result.' ('.$update.')');
        
        $result = $speaker->getLevelVolume();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::VOLUME, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::VOLUME.' = '.$result.' ('.$update.')');
        $result = $speaker->isMuted();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::MUTED, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::MUTED.' = '.$result.' ('.$update.')');
        
        $result = $speaker->getStatePlay();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::STATUS, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::STATUS.' = '.$result.' ('.$update.')');
        $result = $speaker->isShuffle();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::SHUFFLE, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::SHUFFLE.' = '.$result.' ('.$update.')');
        $result = $speaker->getStateRepeat();
        $update |= $this->checkAndUpdateCmd(SoundTouchConfig::REPEAT, $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::REPEAT.' = '.$result.' ('.$update.')');
        
        $result = $speaker->getTrackArtist();
        $update |= ($this->checkAndUpdateCmd(SoundTouchConfig::TRACK_ARTIST, $result) && $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::TRACK_ARTIST.' = '.$result.' ('.$update.')');
        $result = $speaker->getTrackTitle();
        $update |= ($this->checkAndUpdateCmd(SoundTouchConfig::TRACK_TITLE, $result) && $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::TRACK_TITLE.' = '.$result.' ('.$update.')');
        $result = $speaker->getTrackAlbum();
        $update |= ($this->checkAndUpdateCmd(SoundTouchConfig::TRACK_ALBUM, $result) && $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::TRACK_ALBUM.' = '.$result.' ('.$update.')');
        $result = $speaker->getTrackImage();
        $update |= ($this->checkAndUpdateCmd(SoundTouchConfig::TRACK_IMAGE, $result) && $result);
        log::add('BoseSoundTouch', 'debug', 'Response '.SoundTouchConfig::TRACK_IMAGE.' = '.$result.' ('.$update.')');

        // Données supplémentaires
        $info = $this->getCmd(null, SoundTouchConfig::SOURCE);
        if (is_object($info)) {
            $datas = $speaker->getArrayNowPlaying();
            $info->setConfiguration('playing', $datas);

            $preview = $speaker->getPreviewImage();
            
            if ( substr($preview, 0, 5) == 'local' ) {
                $previewImage = 'plugins/BoseSoundTouch/core/template/dashboard/images/'.strtolower(substr($preview, 8)).'.png';
            } elseif ( substr($preview, 0, 4) == 'http' ) {
                $cacheImg = realpath(__DIR__ . '/../../images').'/cache-preview-'.$this->getId().'.png';
                if ( $preview != $info->getConfiguration('preview') ) {
                    file_put_contents($cacheImg, file_get_contents($preview));
                }
                $previewImage = 'plugins/BoseSoundTouch/images/cache-preview-'.$this->getId().'.png?'.md5($preview);
            } else {
                if ( file_exists($cacheImg) ) @unlink($cacheImg);
            }
            $info->setConfiguration('preview', $previewImage);
            log::add('BoseSoundTouch', 'debug', 'Preview Image = '.$preview.' -> '.$previewImage);

            $info->save();
            log::add('BoseSoundTouch', 'debug', 'Données en cours de lecture = '.print_r($datas, true));
        }

        if ($update) {
            BoseSoundTouch::refreshWidget();
            log::add('BoseSoundTouch', 'debug', 'WIDGET rafraîchit...');
        }
        log::add('BoseSoundTouch', 'debug', '--------------------------------------------------------------');
    }


    /**
     * Rafraichissement des présélections
     */
    public function updatePresets()
    {
        $hostname = $this->getConfiguration('hostname');
        log::add('BoseSoundTouch', 'debug', '=== PRESETS ==================================================');
        log::add('BoseSoundTouch', 'debug', "Rafraichissement des présélections depuis '$hostname'");

        // Paramètre de l'adresse de l'enceinte
        $hostname = $this->getConfiguration('hostname');
        // Déclaration de l'API
        $speaker = new JeedomSoundTouchApi($hostname);

        foreach ($this->getCmd('action') as $command) {
            switch ($command->getLogicalId()) {
                case SoundTouchConfig::PRESET_1 :
                case SoundTouchConfig::PRESET_2 :
                case SoundTouchConfig::PRESET_3 :
                case SoundTouchConfig::PRESET_4 :
                case SoundTouchConfig::PRESET_5 :
                case SoundTouchConfig::PRESET_6 :
                    $id = intval(substr($command->getLogicalId(), -1, 1));
                    $cacheImg = realpath(__DIR__ . '/../../images') . '/cache-p' . $id . '-' . $this->getId() . '.png';
                    log::add('BoseSoundTouch', 'debug', $cacheImg);
                    if ( $preset = $speaker->getPresetByNum($id) ) {

                        if ( !$preset['image']) {
                            switch ($preset['source']) {
                                case 'LOCAL_INTERNET_RADIO': $preset['image'] = realpath(__DIR__ . '/..').'/template/dashboard/images/local_internet_radio.png'; break;
                                case 'STORED_MUSIC': $preset['image'] = realpath(__DIR__ . '/..').'/template/dashboard/images/stored_music.png'; break;
                                case 'LOCAL_MUSIC': $preset['image'] = realpath(__DIR__ . '/..').'/template/dashboard/images/local_music.png'; break;
                            }
                        }

                        // Compare pour voir si changement
                        $dataOld = $command->getConfiguration('datas');
                        if ( $dataOld['image'] != $preset['image'] ) {
                            file_put_contents($cacheImg, file_get_contents($preset['image']));
                        }

                        // Sauvegarde les données de la présélection dans la commande
                        $preset['cache'] = 'plugins/BoseSoundTouch/images/cache-p' . $id . '-' . $this->getId() . '.png?'.substr(md5($preset['image']), 0, 5);
                        $command->setConfiguration('datas', $preset);
                        log::add('BoseSoundTouch', 'debug', $command->getLogicalId().' = ('.$preset['source'].') '.$preset['name'].' - '.$preset['image'].' - '.$preset['cache']);

                    } else {
                        $command->setConfiguration('datas', array());
                        if ( file_exists($cacheImg) ) {
                            @unlink($cacheImg);
                        }
                    }
                    $command->save();
                    break;
            }
        }

        log::add('BoseSoundTouch', 'debug', '--------------------------------------------------------------');
        BoseSoundTouch::refreshWidget();
    }


    /**
     * Met à jour les commandes du Plugin
     */
    public function updateCommandSoundTouch()
    {
        // Ajoute les infos
        foreach (SoundTouchConfig::getConfigInfos() as $config) {
            $this->addCommandSoundTouch($config);
        }
        // Ajoute les actions
        foreach (SoundTouchConfig::getConfigCmds() as $config) {
            $this->addCommandSoundTouch($config);
        }
    }


    /**
     * Ajout des commandes à Jeedom
     * 
     * @param Array $config : Configuration de la commande
     */
    public function addCommandSoundTouch(Array $config)
    {
        $cmdSoundTouch = $this->getCmd(null, $config['logicalId']);
        if ( !is_object($cmdSoundTouch) ) {
            $cmdSoundTouch = new BoseSoundTouchCmd();
        }
        $cmdSoundTouch->setName(__($config['name'], __FILE__));
        $cmdSoundTouch->setLogicalId( $config['logicalId'] );
        $cmdSoundTouch->setEqLogic_id( $this->getId() );
        $cmdSoundTouch->setType( $config['type'] );
        $cmdSoundTouch->setSubType( $config['subType'] );
        $cmdSoundTouch->setOrder( $config['order'] );
        if (isset($config['codekey'])) $cmdSoundTouch->setConfiguration( 'codekey', $config['codekey'] );
        if (isset($config['icon'])) $cmdSoundTouch->setDisplay( 'icon', '<img src="plugins/BoseSoundTouch/images/'.$config['icon'].'.png" style="width:20px;height:20px;">' ); //<i class="fa '.$config['icon'].'"></i>
        if (isset($config['forceReturnLineAfter'])) $cmdSoundTouch->setDisplay( 'forceReturnLineAfter', $config['forceReturnLineAfter'] );
        if (isset($config['unity'])) $cmdSoundTouch->setUnite( $config['unity'] );
        //$cmdSoundTouch->setDisplay( 'generic_type', $config['generic_type'] ); // ???
        $cmdSoundTouch->save();
    }

}

class BoseSoundTouchCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {

        $soundTouch = $this->getEqLogic();

        // Paramètre de l'adresse de l'enceinte
        $hostname = $soundTouch->getConfiguration('hostname');
        $idCommand = $this->getLogicalId();

        if ($idCommand == 'REFRESH') {

            $soundTouch->updatePresets();
            $soundTouch->updateInfos();

        } else {

            $codeKey = $this->getConfiguration('codekey');
            $speaker = new JeedomSoundTouchApi($hostname, false);
            if ( $codeKey != '' ) {
                $this->execCmdKeySpeaker($speaker, $codeKey, $idCommand, $hostname);
                switch ($idCommand) {
                    case SoundTouchConfig::PLAY_PAUSE:
                    case SoundTouchConfig::VOLUME_DOWN:
                        $cmdMuted = $soundTouch->getCmd(null, SoundTouchConfig::MUTED);
                        $valueMuted = $cmdMuted->execCmd();
                        log::add('BoseSoundTouch', 'debug', "VOLUME INFO : MUTE = $valueMuted");
                        if ($valueMuted) {
                            $this->execCmdKeySpeaker($speaker, SoundTouchConfig::MUTE, $idCommand, $hostname);
                        }
                        break;
                }
            } else {
                switch ($idCommand) {
                    case SoundTouchConfig::TV :
                        $response = $speaker->selectTV();
                        log::add('BoseSoundTouch', 'debug', "ACTION : SELECT $idCommand -> ".( ($response) ? 'OK' : 'NOK'));
                        if ( !$response ) log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand -> ".$speaker->getMessageError() );
                        break;
                    case SoundTouchConfig::BLUETOOTH :
                        $response = $speaker->selectBlueTooth();
                        log::add('BoseSoundTouch', 'debug', "ACTION : SELECT $idCommand -> ".( ($response) ? 'OK' : 'NOK'));
                        if ( !$response ) log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand -> ".$speaker->getMessageError() );
                        break;
                    case SoundTouchConfig::VOLUME_SET:
                        $cmdMuted = $soundTouch->getCmd(null, SoundTouchConfig::MUTED);
                        $valueMuted = $cmdMuted->execCmd();
                        log::add('BoseSoundTouch', 'debug', "VOLUME INFO : MUTE = $valueMuted");
                        if ($valueMuted) {
                            $this->execCmdKeySpeaker($speaker, SoundTouchConfig::MUTE, $idCommand, $hostname);
                        }
                        $response = $speaker->setVolume($_options['slider']);
                        log::add('BoseSoundTouch', 'debug', "ACTION : VOLUME ".$_options['slider']." -> ".( ($response) ? 'OK' : 'NOK'));
                        if ( !$response ) log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand -> ".$speaker->getMessageError() );
                    default:
                        log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand sur l'enceinte '$hostname' - Touche NULL");
                        break;
                }
            }
            
            //$soundTouch->updateInfos();
        }

        return;

    }


    private function execCmdKeySpeaker($speaker, $codeKey, $idCommand, $hostname)
    {
        log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand sur l'enceinte '$hostname' - Touche $codeKey");
        $response = $speaker->sendCommand($codeKey);
        log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand -> ".( ($response) ? 'OK' : 'NOK'));
        if ( !$response ) log::add('BoseSoundTouch', 'debug', "ACTION : $idCommand -> ".$speaker->getMessageError() );
    }

    /*     * **********************Getteur Setteur*************************** */
}


