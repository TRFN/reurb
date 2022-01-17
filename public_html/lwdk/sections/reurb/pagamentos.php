<?php

	trait pagamentos {
		function page_pagamentos(UITemplate $content, $modelo = "financeiro/pagamentos", $vars = []){
			$content->minify = true;

			$vars = array_merge($vars,["nav-meses"=>"", "content-meses"=>"", "ano" => date("Y"), "mes" => date("m"), "dia" => date("d")]);

			$vars["thead-inad"] = "<th style='text-transform: uppercase; width: 80px;'>Data</th><th style='text-transform: uppercase; width: 350px;'>Nome</th><th style='text-transform: uppercase; width: 60px;'>Parcela</th><th style='text-transform: uppercase; width: 250px;'>Imovel</th><th style='text-transform: uppercase; width: 80px;'>Atraso</th><th style='text-transform: uppercase; width: 80px;'>Status</th>";

			$vars["tbody-inad"] = "";

			foreach($this->get_clientes_status() as $cli){
				foreach($cli["pendentes"] as $pendencia){
					$imovel = $this->database()->query("imoveis", "id = {$pendencia[1]}");
					if(count($imovel)){
						$imovel = $imovel[0];
						$cor = (140+$pendencia[2]);
						$pendencia["3-ext"] = clsTexto::valorPorExtenso($pendencia[3], false, false);
						$vars["tbody-inad"] .= "<tr data-parc=\"{$pendencia[3]}\" style='background-color: hsl({$cor},60%,99%)'><td>{$pendencia[0]}</td><td><a style='color: #000!important;' target=_blank href='/cliente/editar/{$cli["id"]}/'>{$cli["nome"]}</a></td><td>{$pendencia[3]} ({$pendencia["3-ext"]})</td><td><a style='color: #000!important;' target=_blank href='/imovel/editar/{$imovel["id"]}/'>Ver Imóvel</a></td><td>{$pendencia[2]} dia(s)</td><td class=status></td></tr>";
					}
				}
			}

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
						<h4>Pagamentos deste mês</h4>
							<table id="table-'.$mes[0].'" class="table table-bordered table-hover table-striped">
					        <thead>
					            <tr>
					                <th class="text-center text-uppercase" style="width: 50px!important;">Data</th>
					                <th class="text-center text-uppercase" style="width: 120px!important;">Nome</th>
					                <th class="text-center text-uppercase" style="width: 200px!important;">Detalhes</th>
					                <th class="text-center text-uppercase" style="width: 200px!important;">Pagamento</th>
					                <th class="text-center text-uppercase" style="width: 80px!important;">Status</th>
					            </tr>
					        </thead>
		        			<tbody>
		        			</tbody>
	    				</table>
					</div>
					<br><br>
				</div>';
			}

			$vars["data"] = date("Y-m-d");

			// $this->dbg($vars);

            $content = $this->simple_loader($content, $modelo, $vars);

            echo $content->getCode();
        }

		function page_inadimplentes(UITemplate $content){
			$this->page_pagamentos($content, "financeiro/inadimplentes");
        }

		function page_boletos(UITemplate $content){
			$content->minify = false;
			$this->page_pagamentos($content, "financeiro/boletos", ["hoje" => date("Y-m-d")]);
		}

		function page_pagamentos_data($fn=false){
			$valores = [];


			$clientes = [];

			foreach($this->get_clientes() as $cliente){
				$conjuge = $this->database()->query("conjuges", "id = {$cliente["id"]}");
				$imoveis = $this->database()->query("imoveis",  "cliente = {$cliente["id"]}");
				$exportacao = $this->database()->query("expbanco", "cliente = {$cliente["id"]}");

				foreach(array_keys($imoveis) as $index){
					$imov_id = $imoveis[$index]["id"];
					// $this->dbg($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}"));
					$imoveis[$index]["pgtos"] = [];
					foreach($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}") as $pgto){
						$imoveis[$index]["pgtos"][$pgto["data"]] = $pgto["status"];
					}
					$imoveis[$index]["vendedor"] = $this->database()->query("vendedores", "id = {$imoveis[$index]["vendedor"]}");
					$imoveis[$index]["vendedor"] = isset($imoveis[$index]["vendedor"][0])?$imoveis[$index]["vendedor"][0]:["id"=>"","nome"=>""];

					// foreach(array_keys($imoveis[$index]["pgtos"]) as $pgt){
					// 	$imoveis[$index]["pgtos"][$pgt] =
					// }
				}
				$imov = 0;
				foreach(array_keys($exportacao) as $index){
					if(isset($imoveis[$imov]["id"])){
						$imov_id = $imoveis[$imov]["id"];
					} else {
						$imov_id = 0;
					}

					// $this->dbg($this->database()->query("pgtos", "cliente = {$cliente["id"]} and imovel = {$imov_id}"));

					if(!isset($imoveis[$imov]["pgtos"])){
						$imoveis[$imov]["pgtos"] = [];
					}
					$parc = 1;
					$protect_while = 0;
					while($exportacao[$index]["dados"]["valor"] > 0 && $protect_while < 50){
						$protect_while++;
						if(isset($imoveis[$imov]["valor-parcela-{$parc}"]) && !empty($imoveis[$imov]["valor-parcela-{$parc}"])){
							$exportacao[$index]["dados"]["valor"] -= (float)str_replace(",", ".", str_replace(".", "", str_replace("R$ ", "", $imoveis[$imov]["valor-parcela-{$parc}"])));

							$mes = $parc - 1;
							$mes = date("d/", strtotime($imoveis[$imov]["data"])) . date("m/Y", strtotime(("{$imoveis[$imov]["data"]} +{$mes} Month")));

							// $this->dbg($mes);

							if(!isset($imoveis[$imov]["pgtos"][$mes])){
								$imoveis[$imov]["pgtos"][$mes] = "pg";
							}

							$parc++;
						}
					}

					// foreach(array_keys($imoveis[$index]["pgtos"]) as $pgt){
					// 	$imoveis[$index]["pgtos"][$pgt] =
					// }

					// $this->dbg($imoveis[$imov]);

					$imov++;
				}
				$clientes[] = [$cliente, $conjuge, $imoveis];
			}

			$result = (["valores" => $clientes, "checksum"=>sha1(json_encode($clientes))]);
			return($fn===true)?$result:$this->json($result);
		}

		function page_change_status(){
			if(count($this->database()->query("pgtos", "cliente = {$_POST["data"][0]} and imovel = {$_POST["data"][2]} and data = {$_POST["data"][3]}")) > 0){
				$this->database()->setWhere("pgtos", "cliente = {$_POST["data"][0]} and imovel = {$_POST["data"][2]} and data = {$_POST["data"][3]}", ["status" => ($_POST["data"][4] == 1?"pg":'not')]);
			} else {
				$this->database()->push("pgtos", array(
					array(
						"cliente" => $_POST["data"][0],
						"imovel" => $_POST["data"][2],
						"vendedor" => $_POST["data"][1],
						"data" => $_POST["data"][3],
						"status" => ($_POST["data"][4] == 1?"pg":'not')
					)
				));
			}
		}

		function page_aplicar_automatico(){
			$data = json_decode($_POST["dado"], true);
			$this->database()->push("expbanco", array($data), "log_remove");
		}

		function page_get_automatico(){
			$this->json($this->database()->get("expbanco"));
		}

		function page_deletar_excel(){
			unlink($_POST['file']);
		}

		function page_pagamentos_gerar_relatorio(){
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
					["Data", "Cliente", "Vendedor", "Endereço", "Produto", "Parcela", "Status", "Valor"],
					$mes
				];
			};

			$ma = -1;

			$boletos = $_POST["dados"];

			$filter->set($req["data-inicio"]);

			$boletos = ($filter->filter($req["data-final"], 0, $boletos));

			$data2 = [];

			foreach($boletos as $boleto){
				$k = strtotime($boleto[0]);

				while(isset($data2[$k])){
					$k++;
				}

				$data2[$k] = $boleto;
			}

			ksort($data2);

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
					$to = "I{$ln}"; // or any value
					$tom = "C{$ln}"; // or any value
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
					$to = "I{$ln}"; // or any value
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

			$excel->Instance()->getActiveSheet()->getColumnDimension('A')->setWidth(30);
			$excel->Instance()->getActiveSheet()->getColumnDimension('B')->setWidth(70);
			$excel->Instance()->getActiveSheet()->getColumnDimension('C')->setWidth(50);
			$excel->Instance()->getActiveSheet()->getColumnDimension('D')->setWidth(100);
			$excel->Instance()->getActiveSheet()->getColumnDimension('E')->setWidth(40);
			// $excel->Instance()->getActiveSheet()->getStyle('E')->getAlignment()->setWrapText(true);
			$excel->Instance()->getActiveSheet()->getColumnDimension('F')->setWidth(30);
			$excel->Instance()->getActiveSheet()->getColumnDimension('G')->setWidth(30);
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
	}

?>
