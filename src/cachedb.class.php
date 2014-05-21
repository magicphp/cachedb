<?php	
    /**
     * Singleton class to use memcached
     * 
     * @package     MagicPHP CacheDB
     * @author      André Ferreira <andrehrf@gmail.com>
     * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
     */

    class CacheDB extends Db{
        /**
         * Objeto MemCached
         * 
         * @var object
         * @access protected
         */
        protected $oMemCache;
        
        /**
         * Magic function to return connections
         * 
         * @param string $sName Connection name
         * @return boolean
         */
        public static function __callStatic($sName, $aArgs = null){
            $oThis = self::CreateInstanceIfNotExists();
                                  
            if(array_key_exists($sName, $oThis->aConnections))
                return $oThis->aConnections[$sName]["resource"];
            else
                return false;               
        }
        
        /**
         * Function to create connections
         * 
         * @static
         * @access public
         * @param string $sName Connection name
         * @param string $sDrive Drive database to be used (mysql, postgresql, sqlite, mongodb)
         * @param string $sHostname Hostname or path to the database
         * @param string $sUsername User access to the database
         * @param string $sPassword Password to access the database
         * @param string $sSchema Schema to be used (for MySQL, PostgreSQL and MongoDB)
         * @param integer $iPort Door access to the database (for MySQL, PostgreSQL and MongoDB) 
         * @return void
         */
        public static function CreateConnection($sName, $sDrive = "mysql", $sHostname, $sUsername = null, $sPassword = null, $sSchema = null, $iPort = 3306){
            $oThis = self::CreateInstanceIfNotExists();
            Storage::SetArray("class.list", "db", Storage::Join("dir.core", "db" . SP));
            $oThis->aConnections[$sName] = array("drive" => $sDrive);
                        
            switch(strtolower($sDrive)){
                case "mysql": $oThis->aConnections[$sName]["resource"] = new MySQLCache($sHostname, $sUsername, $sPassword, $sSchema, $iPort); break;
                default: unset($oThis->aConnections[$sName]); break;
            }
        }
        
        /**
         * Function for setting the encoding of the database
         * 
         * @static
         * @access public
         * @param string $sName
         * @param string $sCharset
         * @return boolean
         */
        public static function SetCharset($sName, $sCharset = "UTF8"){
            $oThis = self::CreateInstanceIfNotExists();
                       
            if(array_key_exists($sName, $oThis->aConnections)){
                switch($oThis->aConnections[$sName]["drive"]){
                    case "mysql": return $oThis->aConnections[$sName]["resource"]->SetCharset($sCharset); break;
                    default: return false; break;
                }
            }
        }
       
        /**
         * Função para iniciar serviço de armazenamento de cache em memória
         * 
         * @access public
         * @param string $sHostname Hostname do servidor de cache
         * @param integer $iPort Porta do servidor (Padrão: 11211)
         * @return boolean
         */
        public static function Start($sHostname, $iPort = 11211){
            $oThis = self::CreateInstanceIfNotExists();

            if(class_exists("memcached")){
                if(!empty($sHostname) && is_int($iPort)){
                    $oThis->oMemCache = new memcached(array("servers" => array($sHostname.":".$iPort), "debug" => false, "compress_threshold" => 10240, "persistant" => true));

                    if(is_object($oThis->oMemCache)){
                        Storage::Set("cachedb.enabled", true);
                        return true;
                    }
                    else{
                        Storage::Set("cachedb.enabled", false);
                        return false;
                    }	
                }
                else{
                    Storage::Set("cachedb.enabled", false);
                    return false;
                }
            }
            else{
                Storage::Set("cachedb.enabled", false);
                return false;
            }
        }

        /**
         * Função para cadastrar variável em cache
         * 
         * @access public
         * @param string $sKey Nome da variável em cache
         * @param mixed $mValue Valor a ser armazenado
         * @return boolean
         */
        public static function Set($sKey, $mValue, $iExp = 3600){
            $oThis = self::CreateInstanceIfNotExists();

            if(is_object($oThis->oMemCache))
                $oThis->oMemCache->delete($sKey);//Bugfix para evitar que a informação não seja atualizada

            return (is_object($oThis->oMemCache)) ? $oThis->oMemCache->add($sKey, $mValue, $iExp) : false;
        }

        /**
         * Função para retornar variável em cache
         * 
         * @access public
         * @param mixed $sKey Nome da variável em cache
         * @return mixed
         */
        public static function Get($mKey){
            $oThis = self::CreateInstanceIfNotExists();			
            return (is_object($oThis->oMemCache)) ? $oThis->oMemCache->get($mKey) : false;
        }
    }