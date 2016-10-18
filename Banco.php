<?php
	define("MYSQL_CONN_ERROR", "Unable to connect to database."); 
	// Ensure reporting is setup correctly 
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
	
	class BancoDados{
		private $nome_banco;
		private $nome_usuario;
		private $senha_banco;
		private $host_banco;
		private $conexao_banco;
		private $status_conexao = true;
		private $status_login = false;
		function __construct(){
			//$this->connect();
		}
		
		public function getStatusConexao(){
			try { 
				$this->connect(); 
				return $this->status_conexao;
			} catch (Exception $e) { 
				echo "Falha na Conexão com Base de Dados" .$e->getMessage(); 
				throw $e; 
				return false;
			} 
		}
		
		public function getStatusLogin(){
			return $this->status_login;
		}
		
		
		public function connect(){
			/*
			$this->nome_banco = "ibolt_empresa";
			$this->nome_usuario = "ibolt_empresa";
			$this->senha_banco = "empresa";
			$this->host_banco = "186.202.152.57:3306";
			*/
			$this->nome_banco = "ibolt_empresa";
			$this->nome_usuario = "daniel";
			$this->senha_banco = "daniel";
			$this->host_banco = "127.0.0.1:3306";
			
			try{
				
				$this->conexao_banco = new mysqli($this->host_banco,$this->nome_usuario,$this->senha_banco);
				$this->conexao_banco->set_charset("utf8");
				$this->conexao_banco->select_db($this->nome_banco);
				$this->status_conexao = true;
			}
			catch(mysqli_sql_exception $e){
				$this->status_conexao = false;
				throw $e; 
			}
		}
		
		public function login(){
			try {
				$this->getStatusConexao();
				// Recupera o login 
				$login = isset($_POST["email"]) ? addslashes(trim($_POST["email"])) : FALSE; 
				// Recupera a senha, a criptografando em MD5 
				//$senha = isset($_POST["senha"]) ? md5(trim($_POST["senha"])) : FALSE;
				$senha = isset($_POST["senha"]) ? $_POST["senha"] : FALSE; 
				
				// Usuário não forneceu a senha ou o login 
				if(!$login || !$senha) $this->falhaLogin();
				
				
				/** 
				* Executa a consulta no banco de dados. 
				* Caso o número de linhas retornadas seja 1 o login é válido, 
				* caso 0 ou mais de 1, inválido. 
				*/ 
				$sql = "SELECT USUARIOS.id_usuario, USUARIOS.nome_usuario, USUARIOS.email_usuario, USUARIOS.senha_usuario FROM USUARIOS 
						WHERE USUARIOS.email_usuario = '" . $login . "'"; 
				$usuario = $this->conexao_banco->query($sql);
				//$usuario = @mysql_query($SQL) or die("Erro no banco de dados!"); 
				$total = $usuario->num_rows;
				//$total = @mysql_num_rows($usuario);
				// Caso o usuário tenha digitado um login válido o número de linhas será 1.. 
				if($total == 1){ 
					// Obtém os dados do usuário, para poder verificar a senha e passar os demais dados para a sessão 
					//$dados = @mysql_fetch_array($usuario); 
					$dados = $usuario->fetch_array(MYSQLI_ASSOC);
					
					// Agora verifica a senha 
					if(!strcmp($senha, $dados["senha_usuario"])){ 	
						// TUDO OK! Agora, passa os dados para a sessão e redireciona o usuário 
						//echo
						$_SESSION["id_usuario"]= $dados["id_usuario"]; 
						$_SESSION["nome_usuario"] = stripslashes($dados["nome_usuario"]); 
						$_SESSION["email_usuario"] = stripslashes($dados["email_usuario"]); 
						$this->status_login = true;
						return; 
					}else $this->falhaLogin(); 
				}else $this->falhaLogin();
			}
			catch (Exception $e) {
				$this->falhaLogin();
			}
		}
		
		private function falhaLogin(){
			unset( $_SESSION['id_usuario'] );
			unset( $_SESSION['nome_usuario'] );
			unset( $_SESSION['email_usuario'] );
			$_SESSION["status_login"] = true;
			header("Location: /sii");
			exit; 
		}
		
		public function verificarSistemasHabilitados(){
			try {
				$this->getStatusConexao();
				$sql = "SELECT EMPRESA.CODIGO, EMPRESA.NOME, EMPRESA.host_banco, EMPRESA.nome_banco, EMPRESA.user_banco, EMPRESA.senha_banco, SISTEMAS.*, USUARIOS.nome_usuario 
						FROM EMPRESA	
						INNER JOIN EMPRESA_SISTEMA ON EMPRESA.CODIGO = EMPRESA_SISTEMA.fk_empresa
						INNER JOIN SISTEMAS ON SISTEMAS.id_sistema = EMPRESA_SISTEMA.fk_sistema
						INNER JOIN USUARIO_EMPRESA_SISTEMA ON USUARIO_EMPRESA_SISTEMA.fk_empresa_sistema = EMPRESA_SISTEMA.id_empresa_sistema
						INNER JOIN USUARIOS ON USUARIOS.id_usuario = USUARIO_EMPRESA_SISTEMA.fk_usuario
						WHERE USUARIOS.id_usuario = " . $_SESSION["id_usuario"];
						
				$sistemas = $this->conexao_banco->query($sql);
				//$result = @mysql_query($SQL) or die("Erro no banco de dados!"); 
				
				// Percorre os registros retornados
				//session_start();
				$_SESSION["dados_acesso"] = array();
				$_SESSION["dados_empresa"] = array();
				
				while($linha = $sistemas->fetch_array(MYSQLI_ASSOC)){
					array_push($_SESSION["dados_acesso"], $linha);
					$_SESSION["dados_empresa"]["cod_empresa"] = $linha["CODIGO"];
					$_SESSION["dados_empresa"]["nome_empresa"] = $linha["NOME"];
					$_SESSION["dados_empresa"]["host_banco_empresa"] = $linha["host_banco"];
					$_SESSION["dados_empresa"]["nome_banco_empresa"] = $linha["nome_banco"];
					$_SESSION["dados_empresa"]["user_banco_empresa"] = $linha["user_banco"];
					$_SESSION["dados_empresa"]["senha_banco_empresa"] = $linha["senha_banco"];
					
					//echo $linha[0] . " - " . $linha[1] . " - " . $linha[2] . " - " . $linha[3] . "<br>";
				}
				// Libera o result set
				$sistemas->close();
				//mysql_free_result($result);
			}catch (Exception $e) {
				$this->falhaLogin();
			}
		}

		public function getBandeiraCartao($idForma){



			try {
				$this->getStatusConexao();
				$sql = "SELECT forma_pagamento.* FROM forma_pagamento WHERE forma_pagamento.id_forma_pagamento = " . $idForma;

				$result = $this->conexao_banco->query($sql);
				
				if ($result){
					$linha = $result->fetch_assoc();
				}
				$result->close();
				return $linha;
			}catch(Exception $e){
				$this->falhaLogin();
			}
		}
		
		public function setTransacao($transacao, $dadosEmpresa){
			//print_r($transacao);
			if ($transacao->getCodigoOperadora() == 1){
				if ($transacao->getCodigo_Erro_Retorno() == '' or $transacao->getCodigo_Erro_Retorno() == NULL){

					try {
						$this->getStatusConexao();
						if ($transacao->getUrl_Autenticacao_Retorno() == "") $transacao->setUrl_Autenticacao_Retorno('null'); 
						if ($transacao->getCodigo_Erro_Retorno() == "") $transacao->setCodigo_Erro_Retorno('null'); 
						if ($transacao->getMsg_Erro_Retorno() == "") $transacao->setMsg_Erro_Retorno('null'); 
						
						if ($transacao->getAutenticacao_Codigo_Retorno() == "") $transacao->setAutenticacao_Codigo_Retorno('null'); 
						if ($transacao->getAutorizacao_Codigo_Retorno() == "") $transacao->setAutorizacao_Codigo_Retorno('null'); 
						if ($transacao->getAutenticacao_Mensagem_Retorno() == "") $transacao->setAutenticacao_Mensagem_Retorno('null'); 
						if ($transacao->getAutorizacao_Mensagem_Retorno() == "") $transacao->setAutorizacao_Mensagem_Retorno('null'); 
						if ($transacao->getAutorizacao_Lr_Retorno() == "") $transacao->setAutorizacao_Lr_Retorno('null'); 
						if ($transacao->getAutenticacao_Eci_Retorno() == "") $transacao->setAutenticacao_Eci_Retorno('null'); 
						if ($transacao->getAutorizacao_Arp_Retorno() == "") $transacao->setAutorizacao_Arp_Retorno('null'); 
						if ($transacao->getAutorizacao_Nsu_Retorno() == "") $transacao->setAutorizacao_Nsu_Retorno('null'); 
						if ($transacao->getCancelamentoDataHoraRetorno() == "") $transacao->SetCancelamentoDataHoraRetorno(NULL); 
						//if ($transacao->getAutenticacao_Eci_Retorno() == "") $transacao->setAutenticacao_Eci_Retorno('null'); 



						$sql = "SELECT * FROM TRANSACAO WHERE tid_transacao_cielo = '{$transacao->getTid_Retorno()}'";

						$result = $this->conexao_banco->query($sql);
						

						if ($result->num_rows > 0) {
							if ($transacao->getStatusGeral() == 6){
								$sql = "UPDATE TRANSACAO SET 
									status_transacao_cielo = '{$transacao->getStatus_Retorno()}',
									pan_transacao_cielo = '{$transacao->getPan_Retorno()}',
									url_autenticacao_cielo = '{$transacao->getUrl_Autenticacao_Retorno()}',
									cod_retorno_autenticacao_cielo = {$transacao->getAutenticacao_Codigo_Retorno()},
									msg_retorno_autenticacao_cielo = '{$transacao->getAutenticacao_Mensagem_Retorno()}', 
									data_hora_retorno_autenticacao = '{$transacao->getAutenticacao_data_hora_Retorno()}',
									eci_autenticacao_cielo = {$transacao->getAutenticacao_Eci_Retorno()},
									cod_retorno_autorizacao_cielo = {$transacao->getAutorizacao_Codigo_Retorno()},
									msg_retorno_autorizacao_cielo = '{$transacao->getAutorizacao_Mensagem_Retorno()}', 
									data_hora_retorno_autorizacao = '{$transacao->getAutorizacao_Data_Hora_Retorno()}',
									data_hora_retorno_cancelamento = '{$transacao->getCancelamentoDataHoraRetorno()}',
									lr_autorizacao_cielo = {$transacao->getAutorizacao_Lr_Retorno()},
									arp_autorizacao_cielo = {$transacao->getAutorizacao_Arp_Retorno()},
									nsu_autorizacao_cielo = {$transacao->getAutorizacao_Nsu_Retorno()},
									cod_erro_retorno_cielo = '{$transacao->getCodigo_Erro_Retorno()}',
									msg_erro_retorno_cielo = '{$transacao->getMsg_Erro_Retorno()}',
									status_geral = {$transacao->getStatusGeral()},
									fk_operadora = {$transacao->getCodigoOperadora()}
								WHERE TRANSACAO.tid_transacao_cielo = '{$transacao->getTid_Retorno()}'";
							}else{
								$sql = "UPDATE TRANSACAO SET 
									status_transacao_cielo = '{$transacao->getStatus_Retorno()}',
									pan_transacao_cielo = '{$transacao->getPan_Retorno()}',
									url_autenticacao_cielo = '{$transacao->getUrl_Autenticacao_Retorno()}',
									cod_retorno_autenticacao_cielo = {$transacao->getAutenticacao_Codigo_Retorno()},
									msg_retorno_autenticacao_cielo = '{$transacao->getAutenticacao_Mensagem_Retorno()}', 
									data_hora_retorno_autenticacao = '{$transacao->getAutenticacao_data_hora_Retorno()}',
									eci_autenticacao_cielo = {$transacao->getAutenticacao_Eci_Retorno()},
									cod_retorno_autorizacao_cielo = {$transacao->getAutorizacao_Codigo_Retorno()},
									msg_retorno_autorizacao_cielo = '{$transacao->getAutorizacao_Mensagem_Retorno()}', 
									data_hora_retorno_autorizacao = '{$transacao->getAutorizacao_Data_Hora_Retorno()}',
									data_hora_retorno_captura = '{$transacao->getDataHoraCapturaRetorno()}', 
									lr_autorizacao_cielo = {$transacao->getAutorizacao_Lr_Retorno()},
									arp_autorizacao_cielo = {$transacao->getAutorizacao_Arp_Retorno()},
									nsu_autorizacao_cielo = {$transacao->getAutorizacao_Nsu_Retorno()},
									cod_erro_retorno_cielo = '{$transacao->getCodigo_Erro_Retorno()}',
									msg_erro_retorno_cielo = '{$transacao->getMsg_Erro_Retorno()}',
									status_geral = {$transacao->getStatusGeral()},
									fk_operadora = {$transacao->getCodigoOperadora()}
								WHERE TRANSACAO.tid_transacao_cielo = '{$transacao->getTid_Retorno()}'";
							}
							
						} else {
							if ($transacao->getStatusGeral() == 5){
								$sql = "insert into transacao
										(fk_pedido, fk_empresa, fk_operadora, valor_transacao, tid_transacao_cielo, status_transacao_cielo,
										pan_transacao_cielo, 
										data_hora_pedido, 
										url_autenticacao_cielo, 
										cod_retorno_autenticacao_cielo,
										msg_retorno_autenticacao_cielo, 
										data_hora_retorno_autenticacao, 
										eci_autenticacao_cielo, 
										cod_retorno_autorizacao_cielo,
										msg_retorno_autorizacao_cielo, 
										data_hora_retorno_autorizacao,
										data_hora_retorno_captura, 
										lr_autorizacao_cielo, 
										arp_autorizacao_cielo,
										nsu_autorizacao_cielo, 
										cod_erro_retorno_cielo,
										msg_erro_retorno_cielo, 
										forma_pgto_cielo, 
										qtde_parcelas, 
										fk_pedido_pagamento, 
										status_geral,
										fk_forma_pagamento,
										taxa,
										valor_liquido)
									values ({$transacao->getId_Pedido()}, 
										{$dadosEmpresa['cod_empresa']}, 
										{$transacao->getCodigoOperadora()}, 
										{$transacao->getDados_Pd_Valor()}, 
										'{$transacao->getTid_Retorno()}', 
										'{$transacao->getStatus_Retorno()}', 
										'{$transacao->getPan_Retorno()}',
										'{$transacao->getDados_Pd_Data_Hora_Retorno()}',
										'{$transacao->getUrl_Autenticacao_Retorno()}',
										{$transacao->getAutenticacao_Codigo_Retorno()}, 
										'{$transacao->getAutenticacao_Mensagem_Retorno()}',
										'{$transacao->getAutenticacao_data_hora_Retorno()}', 
										{$transacao->getAutenticacao_Eci_Retorno()}, 
										{$transacao->getAutorizacao_Codigo_Retorno()}, 
										'{$transacao->getAutorizacao_Mensagem_Retorno()}',
										'{$transacao->getAutorizacao_Data_Hora_Retorno()}',  
										'{$transacao->getDataHoraCapturaRetorno()}',  
										{$transacao->getAutorizacao_Lr_Retorno()}, 
										{$transacao->getAutorizacao_Arp_Retorno()}, 
										{$transacao->getAutorizacao_Nsu_Retorno()},
										'{$transacao->getCodigo_Erro_Retorno()}', 
										'{$transacao->getMsg_Erro_Retorno()}',
										'{$transacao->getDados_Forma_Pgto_Produto()}',
										{$transacao->getDados_Forma_Pgto_Parcelas()},
										{$transacao->getCodPedidoPagamento()},
										{$transacao->getStatusGeral()},
										{$transacao->getTipoFormaPagamento()},
										{$transacao->getTaxa()},
										{$transacao->getLiquido()}
										)";
							}else{
								$sql = "insert into transacao
										(fk_pedido, fk_empresa, fk_operadora, valor_transacao, tid_transacao_cielo, status_transacao_cielo,
										pan_transacao_cielo, 
										data_hora_pedido, 
										url_autenticacao_cielo, 
										cod_retorno_autenticacao_cielo,
										msg_retorno_autenticacao_cielo, 
										data_hora_retorno_autenticacao, 
										eci_autenticacao_cielo, 
										cod_retorno_autorizacao_cielo,
										msg_retorno_autorizacao_cielo, 
										data_hora_retorno_autorizacao,
										lr_autorizacao_cielo, 
										arp_autorizacao_cielo,
										nsu_autorizacao_cielo, 
										cod_erro_retorno_cielo,
										msg_erro_retorno_cielo, 
										forma_pgto_cielo, 
										qtde_parcelas, 
										fk_pedido_pagamento, 
										status_geral,
										fk_forma_pagamento,
										taxa,
										valor_liquido)
									values ({$transacao->getId_Pedido()}, 
										{$dadosEmpresa['cod_empresa']}, 
										{$transacao->getCodigoOperadora()}, 
										{$transacao->getDados_Pd_Valor()}, 
										'{$transacao->getTid_Retorno()}', 
										'{$transacao->getStatus_Retorno()}', 
										'{$transacao->getPan_Retorno()}',
										'{$transacao->getDados_Pd_Data_Hora_Retorno()}',
										'{$transacao->getUrl_Autenticacao_Retorno()}',
										{$transacao->getAutenticacao_Codigo_Retorno()}, 
										'{$transacao->getAutenticacao_Mensagem_Retorno()}',
										'{$transacao->getAutenticacao_data_hora_Retorno()}', 
										{$transacao->getAutenticacao_Eci_Retorno()}, 
										{$transacao->getAutorizacao_Codigo_Retorno()}, 
										'{$transacao->getAutorizacao_Mensagem_Retorno()}',
										'{$transacao->getAutorizacao_Data_Hora_Retorno()}', 
										{$transacao->getAutorizacao_Lr_Retorno()}, 
										{$transacao->getAutorizacao_Arp_Retorno()}, 
										{$transacao->getAutorizacao_Nsu_Retorno()},
										'{$transacao->getCodigo_Erro_Retorno()}', 
										'{$transacao->getMsg_Erro_Retorno()}',
										'{$transacao->getDados_Forma_Pgto_Produto()}',
										{$transacao->getDados_Forma_Pgto_Parcelas()},
										{$transacao->getCodPedidoPagamento()},
										{$transacao->getStatusGeral()},
										{$transacao->getTipoFormaPagamento()},
										{$transacao->getTaxa()},
										{$transacao->getLiquido()}
										)";
							}
							
						}
						
						//echo $sql;
						$result = $this->conexao_banco->query($sql);
						$this->conexao_banco->close();
					}catch(Exception $e){
						echo "Erro de Inserção: 1 - " .$e->getMessage();
					}
				}
			}else if($transacao->getCodigoOperadora() == 2){//rede

				if ($transacao->getCodRetAutorizacao() == 0){
					
					try {
						$this->getStatusConexao();
						if ($transacao->getDadosAdd() == "") $transacao->setDadosAdd('null'); 
						if ($transacao->getCodRetEstorno() == "") $transacao->setCodRetEstorno('null'); 
						if ($transacao->getMsgRetEstorno() == "") $transacao->setMsgRetEstorno('null'); 

						$sql = "SELECT * FROM TRANSACAO WHERE num_sequencial_rede = '{$transacao->getNumSequencRet()}'";

						$result = $this->conexao_banco->query($sql);
						

						if ($result->num_rows > 0) {
							if ($transacao->getStatusGeral() == 6){
								$sql = "UPDATE TRANSACAO SET 
									cod_retorno_estorno_rede = {$transacao->getCodRetEstorno()},
									msg_retorno_estorno_rede = '{$transacao->getMsgRetEstorno()}',
									status_geral = {$transacao->getStatusGeral()},
									data_hora_retorno_cancelamento = '{$transacao->getDataRetCancelamento()}'
								WHERE TRANSACAO.num_sequencial_rede = {$transacao->getNumSequencRet()}";
							}elseif ($transacao->getStatusGeral() == 5){
								$sql = "UPDATE TRANSACAO SET 
									cod_retorno_estorno_rede = {$transacao->getCodRetEstorno()},
									msg_retorno_estorno_rede = '{$transacao->getMsgRetEstorno()}',
									status_geral = {$transacao->getStatusGeral()},
									data_hora_retorno_captura = '{$transacao->getDataRetCaptura()}'
								WHERE TRANSACAO.num_sequencial_rede = {$transacao->getNumSequencRet()}";
							}else{
								$sql = "UPDATE TRANSACAO SET 
									cod_retorno_estorno_rede = {$transacao->getCodRetEstorno()},
									msg_retorno_estorno_rede = '{$transacao->getMsgRetEstorno()}',
									status_geral = {$transacao->getStatusGeral()},
									data_hora_retorno_autorizacao = '{$transacao->getDataRetAutorizacao()}',
									data_hora_retorno_cancelamento = null
								WHERE TRANSACAO.num_sequencial_rede = {$transacao->getNumSequencRet()}";
							}
							
						} else {
							//date("Ymd", strtotime($_POST["dataAutorizacao"]))
							//$transacao->setDataRetAutorizacao(date("Y-m-d", strtotime($transacao->getDataRetAutorizacao())) . ' ' . date("G:i:s"));
							$data = date("Y-m-d", strtotime($transacao->getDataRetAutorizacao()));
							$hora = date("G:i:s");
							$datatime = $data . ' ' . $hora;
							if ($transacao->getStatusGeral() == 5){


								$sql = "insert into transacao
										(fk_pedido, fk_empresa, fk_operadora, valor_transacao, forma_pgto_rede, qtde_parcelas, 
										dados_adicionais_rede,
										cod_retorno_autorizacao_rede,
										msg_retorno_autorizacao_rede,
										num_retorno_autorizacao_rede,
										data_hora_pedido,
										data_hora_retorno_autorizacao,
										data_hora_retorno_captura,
										num_retorno_comprovante_rede,
										num_retorno_autenticacao_rede,
										num_sequencial_rede,
										num_origem_bin_rede,
										cod_retorno_confirmacao_rede,
										msg_retorno_confirmacao_rede,
										cod_retorno_estorno_rede,
										msg_retorno_estorno_rede,
										fk_pedido_pagamento, 
										status_geral,
										fk_forma_pagamento,
										taxa,
										valor_liquido)
									values ({$transacao->getNumPedido()}, 
										{$dadosEmpresa['cod_empresa']}, 
										{$transacao->getCodigoOperadora()}, 
										{$transacao->getTotalTransacao()}, 
										{$transacao->getTipoTransacao()},
									 	{$transacao->getNumParcelas()},
									 	{$transacao->getDadosAdd()},
									 	{$transacao->getCodRetAutorizacao()},
									 	'{$transacao->getMsgRetAutorizacao()}',
									 	{$transacao->getNumRetAutorizacao()},
									 	'{$transacao->getDados_Pd_Data_Hora_Retorno()}',
									 	'{$datatime}',
									 	'{$transacao->getDataRetCaptura()}',
									 	{$transacao->getNumRetComprovVenda()},
									 	{$transacao->getNumRetAutenticacao()},
									 	{$transacao->getNumSequencRet()},
									 	'{$transacao->getNumOrigemBin()}',
									 	{$transacao->getConfCodRet()},
									 	'{$transacao->getConfMsgRet()}',
									 	{$transacao->getCodRetEstorno()},
									 	'{$transacao->getMsgRetEstorno()}',
										{$transacao->getCodPedidoPagamento()},
										{$transacao->getStatusGeral()},
										{$transacao->getTipoFormaPagamento()},
										{$transacao->getTaxa()},
										{$transacao->getLiquido()}
										)";
							}else{
								$sql = "insert into transacao
										(fk_pedido, 
										fk_empresa, 
										fk_operadora, 
										valor_transacao, 
										forma_pgto_rede, 
										qtde_parcelas, 
										dados_adicionais_rede,
										cod_retorno_autorizacao_rede,
										msg_retorno_autorizacao_rede,
										num_retorno_autorizacao_rede,
										data_hora_pedido,
										data_hora_retorno_autorizacao,
										num_retorno_comprovante_rede,
										num_retorno_autenticacao_rede,
										num_sequencial_rede,
										num_origem_bin_rede,
										cod_retorno_confirmacao_rede,
										msg_retorno_confirmacao_rede,
										cod_retorno_estorno_rede,
										msg_retorno_estorno_rede,
										fk_pedido_pagamento, 
										status_geral,
										fk_forma_pagamento,
										taxa,
										valor_liquido)
									values ({$transacao->getNumPedido()}, 
										{$dadosEmpresa['cod_empresa']}, 
										{$transacao->getCodigoOperadora()}, 
										{$transacao->getTotalTransacao()}, 
										{$transacao->getTipoTransacao()},
									 	{$transacao->getNumParcelas()},
									 	{$transacao->getDadosAdd()},
									 	{$transacao->getCodRetAutorizacao()},
									 	'{$transacao->getMsgRetAutorizacao()}',
									 	{$transacao->getNumRetAutorizacao()},
									 	'{$transacao->getDados_Pd_Data_Hora_Retorno()}',
									 	'{$datatime}',
									 	{$transacao->getNumRetComprovVenda()},
									 	{$transacao->getNumRetAutenticacao()},
									 	{$transacao->getNumSequencRet()},
									 	'{$transacao->getNumOrigemBin()}',
									 	{$transacao->getConfCodRet()},
									 	'{$transacao->getConfMsgRet()}',
									 	{$transacao->getCodRetEstorno()},
									 	'{$transacao->getMsgRetEstorno()}',
										{$transacao->getCodPedidoPagamento()},
										{$transacao->getStatusGeral()},
										{$transacao->getTipoFormaPagamento()},
										{$transacao->getTaxa()},
										{$transacao->getLiquido()}
										)";
							}
							
										
						}
						
						//echo $sql;
						$result = $this->conexao_banco->query($sql);
						$this->conexao_banco->close();

						
					}catch(Exception $e){
						echo "Erro de Inserção: 2 - " .$e->getMessage();
					}
				}
			}
			
			
		
		}

		public function getTransacao($transacao, $codEmpresa){
			print_r($transacao);
			try {
				$this->getStatusConexao();
				//echo " -------------- PRIMEIRO SELECT ------------ -> " . $transacao["Codigo"];
				$sql = "SELECT transacao.*, operadoras_cartao.*, operadora_empresa.* FROM TRANSACAO 
							INNER JOIN operadoras_cartao ON operadoras_cartao.id_operadora = transacao.fk_operadora
							INNER JOIN operadora_empresa ON operadora_empresa.fk_operadora = transacao.fk_operadora AND operadora_empresa.fk_empresa = transacao.fk_empresa
						WHERE transacao.fk_pedido_pagamento = " . $transacao["Codigo"] . " AND transacao.fk_empresa = {$codEmpresa} ORDER BY transacao.id_transacao DESC";

				$result = $this->conexao_banco->query($sql);
				
				if ($result){

					$linha = $result->fetch_assoc();
					
					if ($linha == NULL){
                        $formaPgto = $transacao['TipoPagamento'];
                        try {
                        	//echo "--------- SEGUNDO SELECT ------------";
                            $sql1 = "SELECT forma_pagamento_operadora_empresa.*, operadoras_cartao.*, operadora_empresa.*  FROM forma_pagamento_operadora_empresa  
                        				INNER JOIN operadora_empresa ON operadora_empresa.id_operadora_empresa = forma_pagamento_operadora_empresa.fk_operadora_empresa 
                        				INNER JOIN operadoras_cartao ON operadoras_cartao.id_operadora = operadora_empresa.fk_operadora 
                    				WHERE forma_pagamento_operadora_empresa.fk_forma_pagamento = '" . $formaPgto . "'";
                                //echo $transacao["TipoPagamento"];
                                //echo "</br> " . $sql1 . "</br>";
                           
                            
                            $resultOperadora = $this->conexao_banco->query($sql1);
                            
                            if ($resultOperadora) {
                                $linhaOperadora = $resultOperadora->fetch_assoc();
                                
                            }
                            //echo "teste";
                            //$linha["id_operadora"] = $linhaOperadora["id_operadora"];
                            //$linha["nome_operadora"] = $linhaOperadora["nome_operadora"];
                            //$linha["id_operadora_empresa"] = $linhaOperadora["id_operadora_empresa"];
                            $linha = $linhaOperadora;
                            
                            //echo "</br> " . $transacao["TipoPagamento"] . "</br>";
                            //print_r($_SESSION["dados_acesso"][0]["CODIGO"]);
                            
                            $resultOperadora->close();
                        }catch(Exception $e){
                            echo $e->getMessage();
                        }
                    }
				}
				$result->close();
				
				return $linha;
			}catch(Exception $e){
				$this->falhaLogin();
			}
		}

		public function buscarTransacaoRede($numsequencial){
			try {
				$this->getStatusConexao();
				
				$sql = "SELECT transacao.* FROM TRANSACAO
						WHERE transacao.num_sequencial_rede = $numsequencial"; 
				
				//echo $sql;		
				//$result = $this->conexao_banco->query($sql);
				
				$result = $this->conexao_banco->query($sql);
				
				if ($result){
					$linha = $result->fetch_assoc();
				}
				$result->close();
				
				return $linha;
			}catch(Exception $e){
				$this->falhaLogin();
			}
		}

		public function getHistorico($transacao, $codEmpresa){
			//print_r($transacao);
			try {
				$this->getStatusConexao();
				
				$sql = "SELECT transacao.*, operadoras_cartao.nome_operadora FROM TRANSACAO
							INNER JOIN operadoras_cartao ON operadoras_cartao.id_operadora = transacao.fk_operadora
						WHERE transacao.fk_pedido_pagamento = " . $transacao["Codigo"] . " AND transacao.fk_empresa = {$codEmpresa} ORDER BY transacao.id_transacao desc"; 
				
				//echo $sql;		
				//$result = $this->conexao_banco->query($sql);
				
				$result = $this->conexao_banco->query($sql);
				

				$listaHistorico = array();
				$transacao = array();
				
				
				while($linha = $result->fetch_array(MYSQLI_ASSOC)){

					if ($linha["tid_transacao_cielo"] != NULL) $transacao["CodigoTransacao"] = $linha["tid_transacao_cielo"];
					elseif ($linha["num_sequencial_rede"] != NULL) $transacao["CodigoTransacao"] = $linha["num_sequencial_rede"];
					$transacao["NomeOperadora"] =  $linha['nome_operadora'] ;
					$transacao["DataAutorizacao"] =  $linha['data_hora_retorno_autorizacao'] ;
					$transacao["DataAutenticacao"] = $linha['data_hora_retorno_autenticacao'];
					$transacao["DataCaptura"] = $linha['data_hora_retorno_captura'];
					$transacao["DataCancelamento"] = $linha['data_hora_retorno_cancelamento'];	
					array_push($listaHistorico, $transacao);
				}





				/*if ($result){
					$linha = $result->fetch_assoc();
				}*/
				$result->close();
				//print_r($linha);
				return $listaHistorico;
			}catch(Exception $e){
				$this->falhaLogin();
			}	
		}

		function buscaTransacoesPersonalizada($sql, $codEmpresa){
			
			$sql .= " AND transacao.fk_empresa = {$codEmpresa} order by id_transacao desc";
			//echo $sql;
			
			try {
				$this->getStatusConexao();
				$this->conexao_banco->set_charset("utf8");
				$result = $this->conexao_banco->query($sql);
				$lista = array();
				while($linha = $result->fetch_array(MYSQLI_ASSOC)){
					array_push($lista, $linha);
				}
				$result->close();
				//print_r($lista);
				return $lista;
			}catch(Exception $e){
				$this->falhaLogin();

			}
		}
	}
	
?>