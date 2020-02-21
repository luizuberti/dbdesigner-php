<?php
	
	class dbdesigner{	
		private $xml;
		private $adatatype;
		private $ModelName;
		private $VersionStr;
		
		function __construct($xml) {
			$this->xml = simplexml_load_string($xml);
			$this->SetDataType();
			$this->ModelName = (string)$this->xml->SETTINGS->GLOBALSETTINGS['ModelName'];
			$this->VersionStr = (string)$this->xml->SETTINGS->GLOBALSETTINGS['VersionStr'];
			
		}
		
		private function SetDataType(){
			$xml = $this->xml;
			$tmp_datatype = $xml->SETTINGS->DATATYPES->DATATYPE;
			foreach($tmp_datatype as $linha){
				$id   = (int)$linha['ID'];
				$nome = (string)$linha['TypeName'];
				$this->adatatype[$id]= $nome;
			}
		}
		
		public function gerasql_rel(){
			$xml = $this->xml;
			
			$tmp_relacao = $xml->METADATA->RELATIONS->RELATION;
			$relacao = array();
			foreach($tmp_relacao as $linha){
				$SrcTable = (string)$xml->xpath("//METADATA/TABLES/TABLE[@ID=".(string)$linha['SrcTable']."]")[0]["Tablename"];
				$DestTable = (string)$xml->xpath("//METADATA/TABLES/TABLE[@ID=".(string)$linha['DestTable']."]")[0]["Tablename"];
				
				$FKFields= str_replace("\\n","",(string)$linha['FKFields']);
				$SrcCampo = explode("=",$FKFields)[0];
				$DestCampo = explode("=",$FKFields)[1];
				
				$RefDef= explode("\\n",(string)$linha['RefDef']);
				$Matching = explode("=",$RefDef[0])[1];
				$OnDelete = explode("=",$RefDef[1])[1];
				$OnUpdate = explode("=",$RefDef[2])[1];
				
				$relacao[(int)$linha['ID']]=array(
				nome=>(string)$linha['RelationName'],
				SrcTable => $SrcTable,
				DestTable => $DestTable,
				SrcCampo => $SrcCampo,
				DestCampo => $DestCampo,
				Matching => $Matching,
				OnDelete => $OnDelete,
				OnUpdate => $OnUpdate,
				);
				
			}
			
			$tabela = $xml->METADATA->TABLES->TABLE;
			$sql_drop=array();
			$areld=array();
			$arel=array();
			$sql="";
			foreach ($tabela as $tabela_linha){
				$id   = (int)$tabela_linha['ID'];
				$nome_tabela = (string)$tabela_linha['Tablename'];
				$atributo = ((array)$tabela_linha->attributes())["@attributes"];
				$dado[$id] = array(tipo=>'tabela', nome => $nome_tabela , atributo => $atributo );
				
				// ***** colunas da tabela
				$acol=array();
				foreach($tabela_linha->COLUMNS->COLUMN as $coluna_linha){
					$id   = (int)$coluna_linha['ID'];
					$nome_coluna = (string)$coluna_linha['ColName'];
					$atributo = ((array)$coluna_linha->attributes())["@attributes"];
					$dado[$id] = array(tipo=>'coluna', nome => $nome_coluna , atributo => $atributo );
					
				}
				
				// ***** indice da tabela
				$aind=array();
				foreach($tabela_linha->INDICES->INDEX as $indice_linha){
					$id   = (int)$indice_linha['ID'];
					$nome_idx = (string)$indice_linha['IndexName'];
					$atributo = ((array)$indice_linha->attributes())["@attributes"];
					
					$dado[$id] = array(tipo=>'indice', nome => $nome_idx , atributo => $atributo );
				}
				
				// ***** Relacionamento da tabela
				
				$seq=0;
				foreach($tabela_linha->RELATIONS_END->RELATION_END as $relacao_linha){
					$id   = (int)$relacao_linha['ID'];
					$atributo = ((array)$relacao_linha->attributes())["@attributes"];
					
					$dado[$id] = array(tipo=>'indice', atributo => $atributo );
					$nome_rel =$nome_tabela."_ibfk_".(++$seq);
					$idDatatype = $this->tipo_indice((int)$relacao_linha['IndexKind']);
					$colindex = $dado[(int)$relacao_linha->INDEXCOLUMNS->INDEXCOLUMN['idColumn']][nome];
					
					$areld[$nome_rel]="ALTER TABLE $nome_tabela DROP FOREIGN KEY $nome_rel;\n";
					$arel[$nome_rel]="ALTER TABLE $nome_tabela ADD CONSTRAINT $nome_rel FOREIGN KEY(".$relacao[$id][DestCampo].") REFERENCES ".$relacao[$id][SrcTable]."(".$relacao[$id][SrcCampo].") ON DELETE ".$this->tipo_relacionamento($relacao[$id][OnDelete])." ON UPDATE ".$this->tipo_relacionamento($relacao[$id][OnUpdate]).";\n";
					
				}
				
			}
			$sql_tabela .= implode("",$areld);
			$sql_tabela .= implode("",$arel);
			
			$sql .= $sql_tabela;
			$sql = "SET FOREIGN_KEY_CHECKS=0;\n\n\n$sql";
			$sql = "$sql\nSET FOREIGN_KEY_CHECKS=1;\n";
			
			$sql = $this->convertletra($sql);
			$sql = str_replace('\a',"'",$sql);
			return $sql;
		}
		
		public function gerasql_idx(){
			$xml = $this->xml;
			$tabela = $xml->METADATA->TABLES->TABLE;
			$sql_drop=array();
			$aindd=array();
			$aind=array();
			$sql="";
			foreach ($tabela as $tabela_linha){
				$id   = (int)$tabela_linha['ID'];
				$nome_tabela = (string)$tabela_linha['Tablename'];
				$atributo = ((array)$tabela_linha->attributes())["@attributes"];
				$dado[$id] = array(tipo=>'tabela', nome => $nome_tabela , atributo => $atributo );
				
				// ***** colunas da tabela
				$acol=array();
				foreach($tabela_linha->COLUMNS->COLUMN as $coluna_linha){
					$id   = (int)$coluna_linha['ID'];
					$nome_coluna = (string)$coluna_linha['ColName'];
					$atributo = ((array)$coluna_linha->attributes())["@attributes"];
					$dado[$id] = array(tipo=>'coluna', nome => $nome_coluna , atributo => $atributo );
					
				}
				
				// ***** indice da tabela
				
				foreach($tabela_linha->INDICES->INDEX as $indice_linha){
					$id   = (int)$indice_linha['ID'];
					$nome_idx = (string)$indice_linha['IndexName'];
					$atributo = ((array)$indice_linha->attributes())["@attributes"];
					
					$dado[$id] = array(tipo=>'indice', nome => $nome_idx , atributo => $atributo );
					
					$idDatatype = $this->tipo_indice((int)$indice_linha['IndexKind']);
					$colindex = $dado[(int)$indice_linha->INDEXCOLUMNS->INDEXCOLUMN['idColumn']][nome];
					
					if($nome_idx=='PRIMARY'){
						$aindd[]="ALTER TABLE $nome_tabela	DROP PRIMARY KEY;\n";
						$aind[]="ALTER TABLE $nome_tabela	ADD PRIMARY KEY ($colindex);\n";
						}else{
						$aindd[$nome_idx]="ALTER TABLE $nome_tabela DROP INDEX $nome_idx;\n";
						$aind[$nome_idx] ="ALTER TABLE $nome_tabela ADD  INDEX $nome_idx ($colindex);\n";
					}
					
				}
				
			}
			$sql_tabela .= implode("",$aindd);
			$sql_tabela .= implode("",$aind);
			
			$sql .= $sql_tabela;
			
			$sql = "SET FOREIGN_KEY_CHECKS=0;\n\n\n$sql";
			$sql = "$sql\nSET FOREIGN_KEY_CHECKS=1;\n";
			
			$sql = $this->convertletra($sql);
			$sql = str_replace('\a',"'",$sql);
			return $sql;
		}
		
		public function gerasql(){
			$xml = $this->xml;
			$dado=array();
			
			$tmp_relacao = $xml->METADATA->RELATIONS->RELATION;
			$relacao = array();
			foreach($tmp_relacao as $linha){
				$SrcTable = (string)$xml->xpath("//METADATA/TABLES/TABLE[@ID=".(string)$linha['SrcTable']."]")[0]["Tablename"];
				$DestTable = (string)$xml->xpath("//METADATA/TABLES/TABLE[@ID=".(string)$linha['DestTable']."]")[0]["Tablename"];
				
				$FKFields= str_replace("\\n","",(string)$linha['FKFields']);
				$SrcCampo = explode("=",$FKFields)[0];
				$DestCampo = explode("=",$FKFields)[1];
				
				$RefDef= explode("\\n",(string)$linha['RefDef']);
				$Matching = explode("=",$RefDef[0])[1];
				$OnDelete = explode("=",$RefDef[1])[1];
				$OnUpdate = explode("=",$RefDef[2])[1];
				
				$relacao[(int)$linha['ID']]=array(
				nome=>(string)$linha['RelationName'],
				SrcTable => $SrcTable,
				DestTable => $DestTable,
				SrcCampo => $SrcCampo,
				DestCampo => $DestCampo,
				Matching => $Matching,
				OnDelete => $OnDelete,
				OnUpdate => $OnUpdate,
				);
				
			}
			
			
			$tabela = $xml->METADATA->TABLES->TABLE;
			$sql_drop=array();
			$areld=array();
			$arel=array();
			$sql="";
			foreach ($tabela as $tabela_linha){
				$id   = (int)$tabela_linha['ID'];
				$nome_tabela = (string)$tabela_linha['Tablename'];
				$atributo = ((array)$tabela_linha->attributes())["@attributes"];
				$dado[$id] = array(tipo=>'tabela', nome => $nome_tabela , atributo => $atributo );
				
				$StandardInserts=str_replace("\\n","\n",(string)$tabela_linha['StandardInserts']);
				
				$TableOptions=str_replace("\\n","\n",(string)$tabela_linha['TableOptions']);
				
				$TableOptions = $this->separador($TableOptions);
				//var_dump($TableOptions);
				//var_dump($TableOptions[AverageRowLength]);
				$tabela_detalhe="";
				if($TableOptions[NextAutoIncVal]!=null && $TableOptions[NextAutoIncVal]>0){
					$tabela_detalhe.="AUTO_INCREMENT = $TableOptions[NextAutoIncVal]\n";
				}
				if($TableOptions[AverageRowLength]!=null && $TableOptions[AverageRowLength]>0){
					$tabela_detalhe.="AVG_ROW_LENGTH = $TableOptions[AverageRowLength]\n";
				}
				if($TableOptions[MaxRowNumber]!=null && $TableOptions[MaxRowNumber]>0){
					$tabela_detalhe.="MAX_ROWS = $TableOptions[MaxRowNumber]\n";
				}
				if($TableOptions[MaxRowNumber]!=null && $TableOptions[MaxRowNumber]>0){
					$tabela_detalhe.="MIN_ROWS = $TableOptions[MaxRowNumber]\n";
				}
				if($TableOptions[PackKeys]!=null && $TableOptions[PackKeys]>0){
					$tabela_detalhe.="PACK_KEYS = $TableOptions[PackKeys]\n";
				}
				if($TableOptions[TblPassword]!=''){
					$tabela_detalhe.="PASSWORD = '$TableOptions[TblPassword]'\n";
				}
				if($TableOptions[DelayKeyTblUpdates]==1){
					$tabela_detalhe.="DELAY_KEY_WRITE = 1\n";
				}
				if($TableOptions[RowChecksum]==1){
					$tabela_detalhe.="CHECKSUM = 1\n";
				}
				if($TableOptions[RowFormat]==1){
					$tabela_detalhe.="ROW_FORMAT = dynamic\n";
				}
				if($TableOptions[RowFormat]==2){
					$tabela_detalhe.="ROW_FORMAT = fixed\n";
				}
				if($TableOptions[RowFormat]==3){
					$tabela_detalhe.="ROW_FORMAT = compressed\n";
				}
				
				
				$sql_drop[] = "DROP TABLE IF EXISTS ".(string)$tabela_linha['Tablename'].";";
				$tabela_comente = (string)$tabela_linha['Comments'];
				if($tabela_comente != ""){
					$tabela_comente = "COMMENT '$tabela_comente'";
				}
				$sql_tabela ="CREATE TABLE ".(string)$tabela_linha['Tablename']." (\n";
				
				// ***** colunas da tabela
				$acol=array();
				foreach($tabela_linha->COLUMNS->COLUMN as $coluna_linha){
				$id   = (int)$coluna_linha['ID'];
				$nome_coluna = (string)$coluna_linha['ColName'];
				$atributo = ((array)$coluna_linha->attributes())["@attributes"];
				
				$dado[$id] = array(tipo=>'coluna', nome => $nome_coluna , atributo => $atributo );
				
				$idDatatype = $this->tipo_coluna((int)$coluna_linha['idDatatype']);
				
				
				$DatatypeParams = (string)$coluna_linha['DatatypeParams'];
				
				/*se o campo é nulo (1) ou não (0)*/
				$NotNull = (string)$coluna_linha['NotNull'];
				if($NotNull == 1){/*atribui as strings aos índices*/
					$NotNull = "NOT NULL";
				}
				else{
					$NotNull = "NULL";
				}
				
				/*se o campo é auto incremento (1) ou não (0)*/
				$AutoInc = (string)$coluna_linha['AutoInc'];
				if($AutoInc == 1){/*atribui as strings aos índices*/
					$AutoInc = "AUTO_INCREMENT";
					$autoIncremento = true; /*para verificar a existência de auto incremento no final da tabela*/
				}
				else{
					$AutoInc = "";
				}
				
				/*se o campo é chave primária (1) ou não (0)*/
				$PrimaryKey = (string)$coluna_linha['PrimaryKey']; 
				if($PrimaryKey == 1){/*atribui o nome da coluna à chave primária*/
					$chavePrimaria = $nome_coluna; /*agora já sabemos se o campo é chave primária*/
				}
				
				/*se o campo é chave estrangeira (1) ou não (0)*/
				$IsForeignKey = (string)$coluna_linha['IsForeignKey'];
				if($IsForeignKey == 1){/*atribui o nome da coluna e do índice à chave estrangeira*/
					$chaveEstrangeira = $nome_coluna;
				}
				
				$Comments = (string)$coluna_linha['Comments'];
				if($Comments != ""){
					//$Comments = convertletra($Comments);
					$Comments = "COMMENT '$Comments'";
				}
				
				/*de posse de todos os dados, monta o sql das colunas*/
				$acol[]= "  $nome_coluna $idDatatype$DatatypeParams $NotNull $AutoInc $Comments";
				
				}
				
				// ***** indice da tabela
				$aind=array();
				foreach($tabela_linha->INDICES->INDEX as $indice_linha){
					$id   = (int)$indice_linha['ID'];
					$nome_idx = (string)$indice_linha['IndexName'];
					$atributo = ((array)$indice_linha->attributes())["@attributes"];
					
					$dado[$id] = array(tipo=>'indice', nome => $nome_idx , atributo => $atributo );
					
					$idDatatype = $this->tipo_indice((int)$indice_linha['IndexKind']);
					$colindex = $dado[(int)$indice_linha->INDEXCOLUMNS->INDEXCOLUMN['idColumn']][nome];
					
					if($nome_idx=='PRIMARY'){
						$aind[]=" $idDatatype ($colindex)";
						}else{
						$aind[]=" $idDatatype $nome_idx($colindex)";
					}
					
				}
				
				// ***** Relacionamento da tabela
				$seq=0;
				foreach($tabela_linha->RELATIONS_END->RELATION_END as $relacao_linha){
					$id   = (int)$relacao_linha['ID'];
					$atributo = ((array)$relacao_linha->attributes())["@attributes"];
					
					$dado[$id] = array(tipo=>'indice', atributo => $atributo );
					
					$idDatatype = $this->tipo_indice((int)$relacao_linha['IndexKind']);
					$colindex = $dado[(int)$relacao_linha->INDEXCOLUMNS->INDEXCOLUMN['idColumn']][nome];
					$nome_rel =$nome_tabela."_ibfk_".(++$seq);
					$areld[$nome_rel]="ALTER TABLE $nome_tabela DROP FOREIGN KEY $nome_rel;\n";
					$arel[$nome_rel]="ALTER TABLE $nome_tabela ADD CONSTRAINT $nome_rel FOREIGN KEY(".$relacao[$id][DestCampo].") REFERENCES ".$relacao[$id][SrcTable]."(".$relacao[$id][SrcCampo].") ON DELETE ".$this->tipo_relacionamento($relacao[$id][OnDelete])." ON UPDATE ".$this->tipo_relacionamento($relacao[$id][OnUpdate]).";\n";
					
				}
				
				$sql_tabela .= implode(",\n",$acol);
				$sql_tabela .= count($aind)>0 ? ",\n" :"";
				$sql_tabela .= implode(",\n",$aind);
				$sql_tabela .="\n)ENGINE=InnoDB";
				$sql_tabela .= $tabela_detalhe!="" ? "\n$tabela_detalhe" : '';
				$sql_tabela .= $tabela_comente!="" ? "\n$tabela_comente" : '';
				$sql_tabela .=";\n";
				//$sql_tabela .="\n)ENGINE=InnoDB $tabela_comente;\n";
				$sql_tabela .="$StandardInserts\n";
				
				$sql .= $sql_tabela;
			}
			//$sql .= implode("",$areld);
			$sql .= implode("",$arel);
			$sql_drop=implode("\n\n",$sql_drop);
			$sql = "SET FOREIGN_KEY_CHECKS=0;\n\n$sql_drop\n\n$sql";
			$sql = "$sql\nSET FOREIGN_KEY_CHECKS=1;\n";
			
			$sql = $this->convertletra($sql);
			$sql = str_replace('\a',"'",$sql);
			
			return $sql;
			
		}
		
		public function tipo_relacionamento($cod){
			$wret="";
			switch ($cod){/*atribui as strings aos índices...*/
				case 0:
				$wret = "RESTRICT";
				break;
				case 1:
				$wret = "CASCADE";
				break;
				case 2:
				$wret = "SET NULL";
				break;
				case 3:
				$wret = "NO ACTION";
				break;
				case 4:
				$wret = "SET DEFAULT";
				break;
			}
			return $wret;
		}
		public function tipo_indice($cod){
			$wret="";
			switch ($cod){/*atribui as strings aos índices...*/
				case 0:
				$wret = "PRIMARY KEY";
				break;
				case 1:
				$wret = "INDEX";
				break;
				case 2:
				$wret = "UNIQUE INDEX";
				break;
			}
			return $wret;
		}
		
		public function tipo_coluna($cod){
			$wret= $this->adatatype[$cod];
			return $wret;
		}
		
		
		public function convertletra($str){
			$re = '/(\\\\)([0-9]{3})/m';
			preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
			
			foreach($matches as $linha){
				$str = str_replace($linha[0],"&#$linha[2];",$str);
			}
			return html_entity_decode($str);
		}
		
		public function separador($str){
			$str=explode("\n",$str);
			$tmp=array();
			foreach($str as $a => $b){
				if($b=='') continue;
				$b=explode('=',$b);
				$tmp[$b[0]]=$b[1];
			}
			return $tmp;
		}
	}
?>