<?php
    /**
     * Drive do MySQL com sistema integrado de cache com Memcache
     * 
     * @package     MagicPHP CacheDB
     * @author      André Ferreira <andrehrf@gmail.com>
     * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
     */

    class MySQLCache extends MySQL{
        /**
         * Magic function to return table of the database
         * 
         * @access public
         * @param string $sTableName Table name
         * @return \MySQLTableCached
         */
        public function __get($sTableName){
            if(array_key_exists($sTableName, $this->aTables)){
                $oTable = $this->aTables[$sTableName];
                $oTable->bCached = false;
                 
                Events::Remove("BeforeQuery");
                Events::Remove("AfterQuery");
                
                return $oTable;
            }
            else{
                $oTmpMysqlTable = new MySQLTableCached($this->oConnection, $sTableName);
                $this->aTables[$sTableName] = $oTmpMysqlTable;
                return $this->aTables[$sTableName];
            }            
        }
    }
    
    /**
     * Class tables MySQL
     */
    class MySQLTableCached extends MySQLTable{         
         /**
         * Função para definir uso de cache na Query
         * 
         * @access public
         * @return \MySQLTableCached
         */
        public function Cache($iTimeout = 3600){
            Storage::Set("cachedb.timeout", $iTimeout);
            
            Events::Set("BeforeQuery", function($sSQL, $fCallback){
                if(Storage::Get("cachedb.enabled" , false)){
                    $mCache = @CacheDB::Get(sha1($sSQL));
                         
                    if($mCache !== false && !is_null($mCache)){    
                        if($fCallback)
                              $fCallback(json_decode($mCache, true), null);

                        return true;
                    } 
                    else{
                        return false;
                    }
                }
                else{
                    return false;
                }
            });
            
            Events::Set("AfterQuery", function($sSQL, $aResult){
                if(Storage::Get("cachedb.enabled" , false))
                    @CacheDB::Set(sha1($sSQL), json_encode($aResult), Storage::Get("cachedb.timeout", 3600));
            });
                        
            return $this;
        }
    }