<?php
	//session_start();
	//print_r($_SESSION);
	session_start();
	include("Banco.php");
	$banco = new BancoDados();
	
	try { 
		$banco->connect(); 
	} catch (Exception $e) { 
		echo "Falha na Conexão com Base de Dados" .$e->getMessage(); 
	} 
	echo $banco->getStatusConexao();
	if ($banco->getStatusConexao()){
		echo "test";
		$banco->login();
		echo "test";
		echo $banco->getStatusLogin();
		if ($banco->getStatusLogin()) {
			$banco->verificarSistemasHabilitados();
			header("Location: modulos.php");
		}
	}
?>
