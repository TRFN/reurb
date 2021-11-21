<?php

	trait financeiro {
		function page_admin($content){
			$pg = $this->url(1);
			header("Location: /{$pg}/");
		}

		function page_financeiro_form_add(){
			$insert = $_POST;
			$insert["id"] = $this->database()->newID("findb");
			$this->json($this->database()->push("findb", [$insert]));
		}

		function page_financeiro_form_edit(){
			$insert = $_POST;
			// $this->dbg($insert);
			// $insert["id"] = $this->database()->newID("findb");
			$this->json($this->database()->setWhere("findb", "id = {$insert["id"]}", $insert));
		}

		function page_financeiro_form_erase(){
			$insert = $_POST;
			// $this->dbg($insert);
			// $insert["id"] = $this->database()->newID("findb");
			$this->json($this->database()->deleteWhere("findb", "id = {$insert["id"]}"));
		}

		function tratar_dado_lancamento($out){
			unset($out["@CREATED"]);
			unset($out["@CREATED"]);
			unset($out["@ID"]);
			return $out;
		}

		function page_financeiro_data(){
			$valor = 0;
			$valores = [];

			$entrada = $this->database()->query("findb", "sect = entrada");
			$pagamentos = $this->page_pagamentos_data(true);
			// $this->json($pagamentos);
			foreach($pagamentos["valores"] as $pgto){
				foreach($pgto[2] as $k=>$imovel){
					if($imovel!=-1 && isset($pgto[2][$k]["forma-pgto"])){
						$vzs = (int)preg_replace("/[^0-9]/","","{$pgto[2][$k]["forma-pgto"]}");
						for($ivz = 0; $ivz < $vzs; $ivz++){
							$dtv = strtotime("+${ivz} months",strtotime($pgto[2][$k]["data"]));
							// $this->dbg($imovel);
							$dtapply__ = date("d/m/Y", $dtv);

							$dtquery = $this->database()->query("pgtos", "cliente = {$pgto[0]["id"]} and imovel = {$imovel["id"]} and data = {$dtapply__}");

							$dtpg = isset($dtquery[0]) && $dtquery[0]["status"] == "pg";

							$pago = $dtpg ? "pg":"not";

							$parcs = ["primeira","segunda","terceira","quarta","quinta","sexta","sétima","oitava","nona","décima"];

							$txtparcs = $vzs == 1 ? "A Vista" : "{$parcs[$ivz]} parcela";

							$ivzp = $ivz+1;

							$entrada[] = array(
								"sect" => "entrada",
								"nome" => "
									<div><b>Cliente:</b>&nbsp;{$pgto[0]["nome"]}</div>
									<div><b>Vendedor:</b>&nbsp;{$imovel["vendedor"]["nome"]}</div>
									<div><b>Serviço:</b>&nbsp;{$imovel["produto"]}</div>
								",
								"nomeText" => "{$imovel["produto"]} para {$pgto[0]["nome"]} ({$txtparcs})",
								"valor" => $pgto[2][$k]["valor-parcela-{$ivzp}"],
								"tipo" => "Serviço",
								"data" => date("Y-m-d", $dtv),
								"pago" => $pago,
								"cliente" => $pgto[0]["id"],
								"vendedor" => $imovel["vendedor"]["id"],
								"imovel" => $imovel["id"],
								"kdta" => $dtapply__,
								"vars" => ["date" => $dtv, "now" => strtotime(date("Y-m-d")), "string_date" => date("Y-m-d", $dtv)],
								"fixo" => "fn"
							);

						}
					}
				}
			}
			// $this->json($entrada);
			$saida = $this->database()->query("findb", "sect = saida");

			foreach($entrada as $dt){
				if(!isset($dt["apagada"]) || strtotime(date("Y-m-d")) > strtotime(date($dt["apagada"]))){
					// $valor += g_money($dt["valor"]);

					$d = [$dt["data"], explode("-", $dt["data"])];

					if(!isset($valores[$d[1][0]])){
						$valores[$d[1][0]] = [
							"valor" => 0,
							"itens" => []
						];
					}

					if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
						$valores[$d[1][0]]["itens"][$d[1][1]] = [
							"valor" => 0,
							"itens" => []
						];
					}

					if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
						$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
							"valor" => 0,
							"itens" => []
						];
					}

					$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]]["valor"] += g_money($dt["valor"]);
					$valores[$d[1][0]]["itens"][$d[1][1]]["valor"] += g_money($dt["valor"]);
					$valores[$d[1][0]]["valor"] += g_money($dt["valor"]);

					$out = $dt;
					$out["data"] = $d[0];

					if(!isset($out["nomeText"])){

						$query = $this->database()->query("pgtos", "cliente = {$out["id"]} and imovel = -1 and data = {$out['data']}");
						$pg = isset($query[0]) && $query[0]["status"] == "pg";


						$out["pago"] = $pg ? "pg":"not";

					}

					$out["vars"] = ["date"=>strtotime($d[0]),"now"=>strtotime(date("Y-m-d"))];

					$valores
						[$d[1][0]]["itens"]
						[$d[1][1]]["itens"]
						[$d[1][2]]["itens"][] = $this->tratar_dado_lancamento($out);

					// $valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]]["itens"][] = ($dt);

					if($dt["tipo"] == "Aluguel" && 1){
						$compare_date = abs(floor((($dt_apply=strtotime($dt["data-aluguel-ate"]))-(strtotime($dt["data"]))) / 60 / 60 / 24 / 30));

						$mdt = 0;

						// $this->dbg($compare_date);

						// $dt_apply -= 60 * 60 * 24 * 30;


						$dt_apply = strtotime($dt2=date("Y-m-d",strtotime("-${mdt} months",strtotime($dt["data-aluguel-ate"]))));

						// $this->dbg($dt_apply);

						while($dt_apply >= strtotime($dt["data"])){

							$dt_apply = strtotime($dt2=date("Y-m-d",strtotime("-${mdt} months",strtotime($dt["data-aluguel-ate"]))));

							$dt["vars"] = ["date"=>$dt_apply,"string_date"=>date("Y-m-d",$dt_apply) ,"now"=>strtotime(date("Y-m-d"))];

							$d = date("Y-m-d", $dt_apply);

							$d = [$d, explode("-", $d)];

							if(!isset($valores[$d[1][0]])){
								$valores[$d[1][0]] = [
									"valor" => 0,
									"itens" => []
								];
							}

							if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
								$valores[$d[1][0]]["itens"][$d[1][1]] = [
									"valor" => 0,
									"itens" => []
								];
							}

							if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
								$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
									"valor" => 0,
									"itens" => []
								];
							}

							$valores
								[$d[1][0]]["itens"]
								[$d[1][1]]["itens"]
								[$d[1][2]]["valor"] += g_money($dt["valor"]);

							$valores
								[$d[1][0]]["itens"]
								[$d[1][1]]["valor"] += g_money($dt["valor"]);

							$valores
								[$d[1][0]]
								["valor"] += g_money($dt["valor"])*$compare_date;

							$valores
								[$d[1][0]]["itens"]
								[$d[1][1]]["itens"]
								[$d[1][2]]["itens"][] = $this->tratar_dado_lancamento($dt);

							$mdt++;
						}

						while($compare_date--){
							$valor += g_money($dt["valor"]);
						}
					}

					elseif($dt["fixo"] != "fn" && 1){
						// $ano = date("Y", strtotime($dt["data"]));
						// $mes = date("m", strtotime($dt["data"]));

						$compare_date = abs(floor((($dt_apply=strtotime($dt2=date("Y-m-", strtotime($dt["data"])) . $dt["data-receb-dia"]))-($now=strtotime(($dt["fixo"] == "fs"?$dt["data-receb-ate"]:date("Y-m-d"))))) / 60 / 60 / 24 / 30));

						// $this->dbg([$compare_date,$dt_apply,$dt2,$dt["data"],$now]);
						$mdt = 1;

						if($dt_apply > strtotime($dt["data"])){
							// $dt_apply -= 60 * 60 * 24 * 30;
							while(($dt_apply >= strtotime($dt["data-receb-ate"]) || ($dt["fixo"] == "fsv" && $dt_apply >= strtotime(date("Y-m-d")))) and $compare_date > 0){
								// echo "<pre>";
								// var_dump($dt2);

								if(empty($dt["data-receb-dia"])){
									break;
								}

								$dt_apply = strtotime($dt2=date("Y-m-",strtotime("-${mdt} months",strtotime($dt["data"]))) . $dt["data-receb-dia"]);

								$d = date("Y-m-d", $dt_apply);

								$d = [$d, explode("-", $d)];

								if(!isset($valores[$d[1][0]])){
									$valores[$d[1][0]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
									$valores[$d[1][0]]["itens"][$d[1][1]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
									$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["itens"]
									[$d[1][2]]["valor"] += g_money($dt["valor"]);

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["valor"] += g_money($dt["valor"]);

									$valores
										[$d[1][0]]
										["valor"] += g_money($dt["valor"]);
								// echo "<pre>{$valores[$d[1][0]]["valor"]}";
								$valor += g_money($dt["valor"]);

								$dt["vars"] = ["date"=>$dt_apply,"string_date"=>date("Y-m-d",$dt_apply) ,"now"=>strtotime(date("Y-m-d"))];



								$query = $this->database()->query("pgtos", "cliente = {$dt["id"]} and imovel = -1 and data = {$dt_apply}");
								$pg = isset($query[0]) && $query[0]["status"] == "pg";

								$out["pago"] = $pg ? "pg":"not";

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["itens"]
									[$d[1][2]]["itens"][] = $this->tratar_dado_lancamento($dt);

								$mdt++;
							}
						} else {
							// $dt_apply += 60 * 60 * 24 * 30;

							while(($dt_apply <= strtotime($dt["data-receb-ate"]) || ($dt["fixo"] == "fsv" && $dt_apply <= strtotime(date("Y-m-d")))) and $compare_date > 0){

								if(empty($dt["data-receb-dia"])){
									break;
								}

								$dt_apply = strtotime($dt2=date("Y-m-",strtotime("+${mdt} months",strtotime($dt["data"]))) . $dt["data-receb-dia"]);

								$d = date("Y-m-", $dt_apply) . $dt["data-receb-dia"];

								$d = [$d, explode("-", $d)];

								// echo "<pre>";
								//
								// if($d[0] == "1969-12-"): $this->dbg([$dt2,$dt["data"]]); endif;

								if(!isset($valores[$d[1][0]])){
									$valores[$d[1][0]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
									$valores[$d[1][0]]["itens"][$d[1][1]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
									$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
										"valor" => 0,
										"itens" => []
									];
								}

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["itens"]
									[$d[1][2]]["valor"] += g_money($dt["valor"]);

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["valor"] += g_money($dt["valor"]);

								$valores
									[$d[1][0]]
									["valor"] += g_money($dt["valor"]);

								$valor += g_money($dt["valor"]);

								// echo "<pre>{$valores[$d[1][0]]["valor"]}";

								$out = $dt;

								$out["vars"] = ["date"=>$dt_apply,"string_date"=>date("Y-m-d",$dt_apply) ,"now"=>strtotime(date("Y-m-d"))];

								$out["data"] = $d[0];



								$query = $this->database()->query("pgtos", "cliente = {$out["id"]} and imovel = -1 and data = {$out['data']}");
								$pg = isset($query[0]) && $query[0]["status"] == "pg";

								$out["pago"] = $pg ? "pg":"not";

								$valores
									[$d[1][0]]["itens"]
									[$d[1][1]]["itens"]
									[$d[1][2]]["itens"][] = $this->tratar_dado_lancamento($out);
								// echo "<pre>";
								// var_dump($d[1]);
								$mdt++;
							}
							// exit;
						}

						while($compare_date--){
							$valor += g_money($dt["valor"]);
						}
					} else {
						$valor += g_money($dt["valor"]);
					}
				}
			}


			foreach($saida as $dt){

				$d = [$dt["data"], explode("-", $dt["data"])];

				if(!isset($valores[$d[1][0]])){
					$valores[$d[1][0]] = [
						"valor" => 0,
						"itens" => []
					];
				}

				if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
					$valores[$d[1][0]]["itens"][$d[1][1]] = [
						"valor" => 0,
						"itens" => []
					];
				}

				if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
					$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
						"valor" => 0,
						"itens" => []
					];
				}

				$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]]["valor"] -= g_money($dt["valor"]);
				$valores[$d[1][0]]["itens"][$d[1][1]]["valor"] -= g_money($dt["valor"]);
				$valores[$d[1][0]]["valor"] -= g_money($dt["valor"]);

				$dt["vars"] = ["date"=>strtotime($dt["data"]),"now"=>strtotime(date("Y-m-d"))];

				if($dt["fator"] == "n"){
					$query = $this->database()->query("pgtos", "cliente = {$dt["id"]} and imovel = -1 and data = {$dt['data']}");
					$pg = isset($query[0]) && $query[0]["status"] == "pg";

					$dt["pago"] = $pg ? "pg":"not";

					$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]]["itens"][] = ($dt);
				}

				if(isset($dt["fator"]) && $dt["fator"] != "n" && 1){
					// $compare_date = floor((strtotime(date("Y-m-d"))-(($dt_apply=strtotime($dt["data"])))) / 60 / 60 / 24 / (int)$dt["fator"]);

					$dt_apply = strtotime($dt["data"]);

					// $this->dbg([$compare_date,$dt_apply]);

					while($dt_apply <= strtotime(date("Y-m-d"))){
						$d = date("Y-m-d", $dt_apply);

						$d = [$d, explode("-", $d)];

						if(!isset($valores[$d[1][0]])){
							$valores[$d[1][0]] = [
								"valor" => 0,
								"itens" => []
							];
						}

						if(!isset($valores[$d[1][0]]["itens"][$d[1][1]])){
							$valores[$d[1][0]]["itens"][$d[1][1]] = [
								"valor" => 0,
								"itens" => []
							];
						}

						if(!isset($valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]])){
							$valores[$d[1][0]]["itens"][$d[1][1]]["itens"][$d[1][2]] = [
								"valor" => 0,
								"itens" => []
							];
						}

						$valores
							[$d[1][0]]["itens"]
							[$d[1][1]]["itens"]
							[$d[1][2]]["valor"] -= g_money($dt["valor"]);

						$valores
							[$d[1][0]]["itens"]
							[$d[1][1]]["valor"] -= g_money($dt["valor"]);

						$valores
							[$d[1][0]]
							["valor"] -= g_money($dt["valor"]);

						$valor -= g_money($dt["valor"]);

						$out = $dt;

						$out["data"] = $d[0];

						$query = $this->database()->query("pgtos", "cliente = {$out["id"]} and imovel = -1 and data = {$out['data']}");
						$pg = isset($query[0]) && $query[0]["status"] == "pg";

						$out["pago"] = $pg ? "pg":"not";

						$out["vars"] = ["date"=>$dt_apply,"string_date"=>date("Y-m-d",$dt_apply) ,"now"=>strtotime(date("Y-m-d"))];

						$valores
							[$d[1][0]]["itens"]
							[$d[1][1]]["itens"]
							[$d[1][2]]["itens"][] = $this->tratar_dado_lancamento($out);

						$dt_apply = strtotime($dt2=date("Y-m-d",strtotime($dt["fator"],$dt_apply)));
					}

					// while($compare_date--){
					// 	$valor -= g_money($dt["valor"]);
					// }
				} else {
					$valor -= g_money($dt["valor"]);
				}
			}
			// $this->json($pagamentos);
			$this->json(["valores" => $valores, "checksum"=>sha1(json_encode([$valor,$valores]))]);
		}

		function page_financeiro_adicionar_entrada(UITemplate $content){
            $content->minify = true;

			$vars = ["produtos-lanc-fin" => "[]","act"=>"add"];

			$vars["data"] = date("Y-m-d");

            $content = $this->simple_loader($content, "admin/financeiro/adicionar-entrada", $vars);

            echo $content->getCode();
        }

		function page_financeiro_editar_entrada(UITemplate $content){
            $content->minify = true;

			$vars = ["produtos-lanc-fin" => "[]","act"=>"edit"];

			// $vars["data"] = date("Y-m-d");

			$vars["id"] = $this->url(1);

			$busca = $this->database()->query("findb", "sect = entrada and id = {$vars["id"]}");

			if(count($busca) > 0){
				$vars = array_merge($busca[0], $vars);
			}

            $content = $this->simple_loader($content, "admin/financeiro/editar-entrada", $vars);

            echo $content->getCode();
        }

		function page_financeiro_adicionar_saida(UITemplate $content){
            $content->minify = true;

			$vars = ["act"=>"add"];

			$vars["data"] = date("Y-m-d");

            $content = $this->simple_loader($content, "admin/financeiro/adicionar-saida", $vars);

            echo $content->getCode();
        }

		function page_financeiro_editar_saida(UITemplate $content){
			$content->minify = true;

			$vars = ["act"=>"edit"];

			$vars["id"] = $this->url(1);

			$busca = $this->database()->query("findb", "sect = saida and id = {$vars["id"]}");

			if(count($busca) > 0){
				$vars = array_merge($busca[0], $vars);
			}

			$content = $this->simple_loader($content, "admin/financeiro/editar-saida", $vars);

			echo $content->getCode();
		}

		function page_financeiro_home(UITemplate $content){

			$vars = ["nav-meses"=>"", "content-meses"=>"", "ano" => date("Y"), "mes" => date("m"), "dia" => date("d")];

            $content->minify = true;

			$filter = parent::control("util/dates");

			$hoje = date("Y-m-d");

			$filter->set($hoje);

			$vars["hoje"] = $hoje;

			$filter->sub("1 Month");

			$vars["data1mesantes"] = $filter->get("Y-m-d");

			$filter->set($hoje);
			$filter->sum("1 Month");

			$vars["data1mesdepois"] = $filter->get("Y-m-d");

			$filter->set($hoje);
			$filter->sum("1 Year");

			$vars["umanodepois"] = $filter->get("Y-m-d");

			$filter->set($hoje);
			$filter->sub("1 Year");

			$vars["umanoantes"] = $filter->get("Y-m-d");

			$meses = [
				["jan","Janeiro",0],
				["fev","Fevereiro",0],
				["mar","Março",0],
				["abr","Abril",0],
				["mai","Maio",0],
				["jun","Junho",0],
				["jul","Julho",0],
				["ago","Agosto",0],
				["set","Setembro",0],
				["out","Outubro",0],
				["nov","Novembro",0],
				["dez","Dezembro",0]
			];

			foreach($meses as $imes=>$mes){
				$imes = $imes + 1;

				$act = (($mes[2] == 1 || (int)$vars["mes"] == (int)$imes) ? [" active", ' aria-selected="true"'," show active"]:["","",""]);

				$vars["nav-meses"] .= '
				  <a class="nav-item nav-link'.$act[0].'" id="link-'.$mes[0].'" data-toggle="tab" href="#'.$mes[0].'" role="tab" aria-controls="'.$mes[0].'" '.$act[1].'>'.$mes[1].'</a>
				';

				$vars["content-meses"] .= '<div class="tab-pane mes-'.$imes.' fade'.$act[2].'" id="'.$mes[0].'" role="tabpanel" aria-labelledby="'.$mes[0].'">
					<h2>Mês de '.$mes[1].' de <span class="ano">'.$vars["ano"].'</span></h2>
					<br><br>
					<div class="table-responsive">
						<h4>Transações deste mês</h4>
							<table id="table-'.$mes[0].'" class="table table-bordered table-hover table-striped">
					        <thead>
					            <tr>
					                <th style="width: 30px!important;">Dia</th>
					                <th style="width: 220px!important;">Titulo</th>
					                <th style="width: 30px!important;">Valor</th>
					                <th style="width: 30px!important;">Tipo</th>
					                <th style="width: 30px!important;">Status</th>
					                <th style="width: 120px!important;">Opções</th>
					            </tr>
					        </thead>
		        			<tbody>
		        			</tbody>
							<tfooter><tr><td colspan=6 class="text-right"><button class="m-0 mt-2 btn btn-dark m-btn" data-toggle="collapse" data-target="#indices-' . $imes . '">Exibir/Esconder Gráficos</button></td></tr><tr><td class="bg-secondary py-4 collapse show" id="indices-' . $imes . '" colspan=6>
								<div class="indices row p-0 m-0">
									<div class="col-lg col-md-4 col-12">
										<h4>Receita do Mês</h4>
										<h6 class="receita-'.$imes.'">&nbsp;R$ 0,00&nbsp;</h6>
										<div style="font-family: Arial Black;" class="grafico1-'.$imes.'"></div>
									</div>

									<div class="col-lg col-md-4 col-12">
										<h4>Despesas do Mês</h4>
										<h6 class="despesas-'.$imes.'">&nbsp;R$ 0,00&nbsp;</h6>
										<div style="font-family: Arial Black;" class="grafico2-'.$imes.'"></div>
									</div>
								</div>
							</td></tr></tfooter>
	    				</table>
					</div>
					<br><br>
				</div>';
			}

			$vars["data"] = date("Y-m-d");

            $content = $this->simple_loader($content, "admin/financeiro/fluxo", $vars);

            echo $content->getCode();
        }

		function page_financeiro_gerar_relatorio(){
			$req = $_REQUEST;

			$filter = parent::control("util/dates");

			$excel = parent::control("util/excel");
			$excel->SetDocumentTitle("relatorio-" . substr(sha1(date("dmYHis")),0,10));

			$data = [];

			$cabecalho = function($dt){
				$mes = explode("/", $dt);
				$mes = (int)$mes[1];
				$nmes = $mes;
				switch($mes){
					case 1: $mes = "Janeiro"; break;
					case 2: $mes = "Fevereiro"; break;
					case 3: $mes = "Março"; break;
					case 4: $mes = "Abril"; break;
					case 5: $mes = "Maio"; break;
					case 6: $mes = "Junho"; break;
					case 7: $mes = "Julho"; break;
					case 8: $mes = "Agosto"; break;
					case 9: $mes = "Setembro"; break;
					case 10: $mes = "Outubro"; break;
					case 11: $mes = "Novembro"; break;
					case 12: $mes = "Dezembro"; break;
				}

				return [
					["{$_GET["titulo"]} {$mes}"],
					["Data", "Descrição", "Valor", "Tipo", "Status"],
					$mes
				];
			};

			$ma = -1;

			$boletos = $_POST["dados"];

			$filter->set($req["data-inicio"]);

			$boletos = ($filter->filter($req["data-final"], 0, $boletos));

			$data2 = [];

			$entrada_somada = 0;
			$saida_somada = 0;

			foreach($boletos as $boleto){
				if(((int)$req["entrada"] == 1 && $boleto[4] !== "SAIDA") || ((int)$req["saida"] == 1 && $boleto[4] == "SAIDA")){
					$k = strtotime($boleto[0]);

					while(isset($data2[$k])){
						$k++;
					}
					if($boleto[5] == "PAGO"){
						if($boleto[4] !== "SAIDA"){
							$entrada_somada += (float)str_replace(",", ".", str_replace(".", "", preg_replace("/[^0-9\.\,]/", "", $boleto[3])));
						} else {
							$saida_somada += (float)str_replace(",", ".", str_replace(".", "", preg_replace("/[^0-9\.\,]/", "", $boleto[3])));
						}
					}
					$data2[$k] = $boleto;
				}
			}

			ksort($data2);

			$saida_somada = "R$ " . number_format($saida_somada, 2, ",", ".");
			$entrada_somada = "R$ " . number_format($entrada_somada, 2, ",", ".");

			$ln = 1;
			$sep = 2;

			foreach($data2 as $boleto){
				$k = strtotime(array_shift($boleto));

				// while(isset($data[$k])){
				// 	$k++;
				// }
				//
				// $data[$k] = [$ma];
				//
				// $ln++;

				$m = $cabecalho($boleto[0]);

				if($mb=(($map1=$m[2]) != ($map2=$ma))){

					if($ln > 1){
						for($ksep = 0; $ksep < $sep; $ksep++){
							while(isset($data[$k])){
								$k++;
							}

							$data[$k] = [];

							$ln++;
						}
					}

					$map = "{$map1}!={$map2}";

					$ma = $m[2];

					// while(isset($data[$k])){
					// 	$k++;
					// }
					//
					// $data[$k] = [$m[2],$ma,$mb?$map:"false"];
					//
					// $ln++;

					while(isset($data[$k])){
						$k++;
					}

					$data[$k] = $m[0];

					$from = "A{$ln}"; // or any value
					$to = "E{$ln}"; // or any value
					$tom = "E{$ln}"; // or any value
					$styleArray = array(
						'font'  => array(
							'bold'  => true,
							'color' => array('rgb' => 'FFFFFF'),
							'size'  => 26
						),

						'fill' => array(
				            'type' => 'solid',
				            'color' => array('rgb' => $_GET["cor"])
				        ));

					$excel->Instance()->getActiveSheet()->mergeCells("{$from}:{$tom}");
					$excel->Instance()->getActiveSheet()->getStyle("{$from}:{$to}")->applyFromArray($styleArray);

					$ln++;

					while(isset($data[$k])){
						$k++;
					}

					$data[$k] = $m[1];

					$from = "A{$ln}"; // or any value
					$to = "E{$ln}"; // or any value
					$styleArray = array(
						'font'  => array(
							'bold'  => true,
							'color' => array('rgb' => 'FFFFFF'),
							'size'  => 12
						),

						'fill' => array(
				            'type' => 'solid',
				            'color' => array('rgb' => $_GET["cor"])
				        )
					);

					$excel->Instance()->getActiveSheet()->getStyle("$from:$to")->applyFromArray($styleArray);

					$ln++;
				}

				while(isset($data[$k])){
					$k++;
				}

				$ln++;

				$data[$k] = $boleto;
			}
			$fin = [];
			if((int)$req["entrada"] == 1){
				$fin[] = "Entrada total: {$entrada_somada}";
			}

			if((int)$req["saida"] == 1){
				$fin[] = "|";
				$fin[] = "Saída total: {$saida_somada}";
			}

			$styleArray = array(
				'font'  => array(
					'bold'  => true,
					'color' => array('rgb' => 'FFFFFF'),
					'size'  => 26
				),

				'fill' => array(
					'type' => 'solid',
					'color' => array('rgb' => "444444")
				));

			if(count($fin)){
				$from = "A{$ln}"; // or any value
				$to = "E{$ln}"; // or any value
				$excel->Instance()->getActiveSheet()->mergeCells("{$from}:{$to}");
				$excel->Instance()->getActiveSheet()->getStyle("{$from}:{$to}")->applyFromArray($styleArray);
				$ln++;
				$from = "A{$ln}"; // or any value
				$to = "E{$ln}"; // or any value
				$excel->Instance()->getActiveSheet()->mergeCells("{$from}:{$to}");
				$excel->Instance()->getActiveSheet()->getStyle("{$from}:{$to}")->applyFromArray($styleArray);
				$ln++;
				$from = "A{$ln}"; // or any value
				$to = "E{$ln}"; // or any value
				$excel->Instance()->getActiveSheet()->mergeCells("{$from}:{$to}");
				$excel->Instance()->getActiveSheet()->getStyle("{$from}:{$to}")->applyFromArray($styleArray);
				$data[] = [""];
				$data[] = [implode("          ", $fin)];
				$data[] = [""];
			}
			$excel->Instance()->getActiveSheet()->getColumnDimension('A')->setWidth(28);
			$excel->Instance()->getActiveSheet()->getColumnDimension('B')->setWidth(120);
			$excel->Instance()->getActiveSheet()->getColumnDimension('C')->setWidth(28);
			$excel->Instance()->getActiveSheet()->getColumnDimension('D')->setWidth(28);
			$excel->Instance()->getActiveSheet()->getColumnDimension('E')->setWidth(28);
			// $excel->Instance()->getActiveSheet()->getStyle('E')->getAlignment()->setWrapText(true);
			// $excel->Instance()->getActiveSheet()->getColumnDimension('H')->setWidth(30);
			// $excel->Instance()->getActiveSheet()->getColumnDimension('I')->setWidth(25);

			// $this->dbg($data);

			$excel->Instance()->getActiveSheet()->fromArray($data);

			$lastrow = $excel->Instance()->getActiveSheet()->getHighestRow();

			// $styleArray = array(
			// 	'font'  => array(
			// 		'bold'  => true,
			// 		'color' => (int)$req["entrada"] == 1 ? array('rgb' => '168511') : array('rgb' => '851611'),
			// 		'size'  => 14
			// 	));
			//
			// $excel->Instance()->getActiveSheet()->getStyle("B{$lastrow}")->applyFromArray($styleArray);
			//
			// $styleArray = array(
			// 	'font'  => array(
			// 		'bold'  => true,
			// 		'color' => array('rgb' => '851611'),
			// 		'size'  => 14
			// 	));
			//
			// $excel->Instance()->getActiveSheet()->getStyle("D{$lastrow}")->applyFromArray($styleArray);

			$excel->Instance()->getActiveSheet()->getStyle('A1:I'.$lastrow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

			$excel->Instance()->getActiveSheet()->getStyle('A1:I'.$lastrow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

			exit($excel->Save("xls"));
		}

		function page_financeiro_apagar(){
			unlink($_POST["arq"]);
		}
	}

?>
