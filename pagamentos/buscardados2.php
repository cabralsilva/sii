<?php

	@include("../odbc.php");
	session_start();
	$host = $_SESSION["dados_empresa"]["host_banco_empresa"];
	$banco = $_SESSION["dados_empresa"]["nome_banco_empresa"];
	$user = $_SESSION["dados_empresa"]["user_banco_empresa"];
	$senha = $_SESSION["dados_empresa"]["senha_banco_empresa"];
	$bancoCliente = new BancoODBC();
	try {
		$bancoCliente->buscarListaPedidosPendentes($host, $banco, $user, $senha);
		$newList = array();
		foreach($_SESSION["listaPedidos"] as $pedido){
			$pedido = $bancoCliente->buscarListaPedidosPagamento2($pedido);
			//print_r($pedido);
			array_push($newList, $pedido);
		}
		$_SESSION["listaPedidos"] = $newList;
		print_r($_SESSION["listaPedidos"]);
		
	}catch(Exception $e){
		echo "<br />FALHA AO BUSCAR DADOS<br />" .$e->getMessage();
	}
	
	

?>