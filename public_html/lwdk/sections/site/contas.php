<?php
	trait site_contas {
		function sessao_object(){
            $sessao = $this->control("users/session");

            $sessao->database = "contas-loja";

            $sessao->mainkey = "id";

            $sessao->keypass = "password";

            return $sessao;
        }

		function setUData(array $data){
			parent::database()->setWhere($this->sessao_object()->database, "id = " . $this->sessao()->id, $data);
		}

		function getUData(String $data, $fallback = []){
			$query = parent::database()->query($this->sessao_object()->database, "id = " . $this->sessao()->id);

			if(count($query) < 1){
				return $fallback;
			}

			if(!is_array($query[0]) || !isset($query[0][$data])){
				return $fallback;
			}

			return $query[0][$data];
		}

		function pagarme(){
			$social = $this->database()->get("social");

			if(!is_array($social) || !isset($social["contatos"]) || !is_array($social["contatos"]) || !isset($social["contatos"]["chave-pagarme"]) || empty($social["contatos"]["chave-pagarme"])){
				header("Location: /");
			}

			if(empty($this->pagarme)){

				$this->pagarme = parent::control("connect/pagarme");

				$this->pagarme->token($social["contatos"]["chave-pagarme"]);

				$this->pagarme->connect();
			}

			return $this->pagarme;
		}

        function sessao(String $email="",String $senha=""){
            if(empty($email) && $email===$senha){
                $sessid = $this->sessao_object()->session(parent::url(0) !== "session");
                unset($sessid->password);
                return $sessid;
            } else {
                return $this->sessao_object()->connect($email, $senha);
            }
        }

        function page_logout($content){
            $this->sessao_object()->logout();
            header("Location: /");
        }

		function page_session(){
			$this->json($this->sessao_object()->session(false) !== false);
		}

		function page_login(){
			$mthd = $this->request();
            header("Content-Type: application/json");
            if($this->post()){
                $sessao = $this->sessao_object();
				$sessao->expires(300 * (int)(!(bool)(int)$mthd["manter-conectado"]));
                $doc = $this->formatar_cpf_cnpj($mthd["email"]);
                if($doc!==false){
                    $sessao->keyuser = "doc";
                    $sessao->connect($doc, isset($mthd["password"])?$mthd["password"]:" ");
                } else $this->sessao($mthd["email"], isset($mthd["password"])?$mthd["password"]:" ");
	            $this->json($this->sessao());
            } else {
				header("Location: /");
			}
        }

		function page_registrar_conta(){
			$mthd = $this->request();
			$status = array("error" => "Ocorreu um erro desconhecido, tente novamente!");
			$db = $this->sessao_object()->database;

			if(!$this->parseData("nome", $mthd["name"])){
                $status["error"] = "Insira um nome v&aacute;lido!";
            } elseif(!$this->parseData("cpfcnpj", $mthd["doc"])){
                $status["error"] = "Insira um CPF/CNPJ v&aacute;lido!";
            } elseif(!$this->parseData("email", $mthd["email"])){
                $status["error"] = "Insira um email v&aacute;lido!";
            } elseif(strlen($mthd["password"]) < 5){
                $status["error"] = "Senha curta!";
            } elseif($mthd["password"] != $mthd["c_password"]){
                $status["error"] = "As senhas n&atilde;o coincidem";
            } else {
				$hasDoc = (count(parent::database()->query($db, "doc = {$mthd["doc"]}")) > 0);
				$hasEmail = (count(parent::database()->query($db, "email = {$mthd["email"]}")) > 0);

				if($hasDoc){
                    $status["error"] = "Este CPF/CNPJ j&aacute; esta em uso.";
                } elseif($hasEmail){
                    $status["error"] = "O e-mail usado no cadastro j&aacute; esta em uso.<br>Tente outro email.";
                } else {
					$status["error"] = "";

					$id = parent::database()->newID($db);
					$mthd["id"] = $id;
					$mthd["tel"] = "";
					$mthd["enderecos"] = [];
					$p = $mthd["password"];
					$u = $mthd["email"];
					$mthd["password"] = md5($mthd["password"]);
					parent::database()->push($db, array($mthd));
					$this->sessao_object()->connect($u,$p);
				}
			}
			$this->json($status);
		}

		function page_action_change_profile(){
			$uid = $this->sessao()->id;
			$req = $this->request();

			$modify = ["name", "email", "doc", "tel", "password", "c_password"];

			/* SECURITY START */

			foreach(array_keys($req) as $key){
				if(!in_array($key, $modify)){
					unset($req[$key]);
				}
			}

			/* SECURITY END */

			if(!$this->parseData("nome", $req["name"])){
				$error = "Insira um nome v&aacute;lido!";
			} elseif(!$this->parseData("cpfcnpj", $req["doc"])){
				$error = "Insira um CPF/CNPJ v&aacute;lido!";
			} elseif(!$this->parseData("email", $req["email"])){
				$error = "Insira um email v&aacute;lido!";
			} elseif(strlen($req["password"]) < 5 && strlen($req["password"]) > 0){
                $error = "Senha curta!";
            } elseif(strlen($req["password"]) > 0 && $req["password"] != $req["c_password"]){
                $error = "As senhas n&atilde;o coincidem";
            } else {
				$error = "";
				if(strlen($req["password"]) < 1){
					unset($req["password"]);
				} else {
					$req["password"] = md5($req["password"]);
				}
				unset($req["c_password"]);
				parent::database()->setWhere(
					$this->sessao_object()->database,
					"id = {$uid}",
					$req
				);
			}

			$this->json($error);
		}

		function page_action_mudar_endereco(){
			$uid = $this->sessao()->id;
			$req = $this->request();

			parent::database()->setWhere(
				$this->sessao_object()->database,
				"id = {$uid}",
				$req
			);

			$this->json(true);
		}

		function page_action_change_credit_cards(){
			$request = $this->request();
			$id = $this->sessao()->id;
			if(isset($request["d"]) && count($request["d"]) > 0){
				parent::database()->setWhere(
					$this->sessao_object()->database,
					"id = {$id}",
					array("cards" => $request["d"])
				);
			}
		}

		function page_recuperar_conta(UITemplate $content){
			/* Recuperar conta a partir de envio de link via email */

			$idRec = parent::url(1);
			$request = $this->request();

			if(count($query=parent::database()->query("recuperar-contas", "token={$idRec}"))){
				$query2 = parent::database()->query($this->sessao_object()->database, "id={$query[0]["id"]}");
				$query2[0]["error"] = "";
				$query2[0]["pass"] = "";
				if($this->post()){
					if(strlen($request["password"]) < 5){
						$query2[0]["error"] = "Senha muito curta!";
					} elseif($request["password"] !== $request["confirm_password"]){
						$query2[0]["error"] = "As senhas n&atilde;o coincidem!";
						$query2[0]["pass"] = $request["password"];
					} else {
						parent::database()->setWhere($this->sessao_object()->database, "id={$query[0]["id"]}", array("password" => md5($request["password"])));
						parent::database()->deleteWhere("recuperar-contas", "id={$query[0]["id"]}");
						$this->sessao_object()->connect($query2[0]["email"], $request["password"]);
						header("Location: /painel/");
					}
				}

				echo $this->simple_loader($content, "site/recuperar-conta", array_merge($query2[0],array("TITLE" => "Recuperar Conta")))->getCode();
			} elseif($this->post()) {

				if(!$this->parseData("email", $request["email"])){
					$this->json(1);
				} else {
					if(!count($query=parent::database()->query($this->sessao_object()->database, "email={$request["email"]}"))){
						$this->json(1);
					} else {
						$id = sha1(uniqid());

						parent::database()->deleteWhere("recuperar-contas", "id={$query[0]["id"]}");
						parent::database()->push("recuperar-contas", array(array("id" => $query[0]["id"], "token" => $id)), "log_remove");

						$emailContent = "<h1>{$query[0]["name"]}</h1><p>Uma solicita&ccedil;&atilde;o de <b>recupera&ccedil;&atilde;o de conta</b> foi realizada no site <b>{$this->empresa} ({$this->mydomain})</b> recentimente para seu email (<i>{$query[0]["email"]}</i>). Acesse este link para prosseguir com a recupera&ccedil;&atilde;o de conta: <br> <br> <a href='{$this->mydomain}/recuperar_conta/{$id}/'>{$this->mydomain}/recuperar_conta/{$id}/</a> <br> <br> Se n&atilde;o foi voc&ecirc;, desconsidere esse email.";

						$email = parent::control("util/email");

						$email->add($query[0]["email"], $query[0]["name"]);

						$envio = $email->send("Email para recuperar a conta", $emailContent);

						if($envio===true){
							$this->json(0);
						} else {
							$this->json($envio);
						}
					}
				}
				$this->json(2);
			}
			header("Location: /");
		}

		function page_action_fav_toggle(){
			$req = $this->request();
			$id = isset($req["i"]) ? $req["i"]:"";
			if($this->sessao() == false){
				$this->json(0);
			}
			if(!empty($id)){
				$uid = $this->sessao()->id;
				$preset = isset($this->sessao()->favs) ? $this->sessao()->favs:array();
				if(in_array($id, $preset)){
					$key = array_search($id, $preset);
					if($key !== false){
					    unset($preset[$key]);
					}
				} else {
					$preset[] = $id;
				}

				parent::database()->setWhere($this->sessao_object()->database, "id={$uid}", array("favs"=>$preset));
				$this->json(1);
			}
			$this->json(0);
		}

		function page_entrar(UITemplate $content){
			if($this->sessao() !== false){
				header("Location: /meu_perfil/");
			}
			echo $this->simple_loader($content, "site/login", array("TITLE" => "Entrar / Se Cadastrar"))->getCode();
		}

		function page_meu_perfil(UITemplate $content){
			if($this->sessao() === false){
				header("Location: /entrar/");
			}
			echo $this->simple_loader($content, "site/meusdados", array("TITLE" => "Meu Perfil"))->getCode();
		}

		function page_favoritos(UITemplate $content){
			if($this->sessao() === false){
				header("Location: /entrar/");
			}

			// $this->dbg("id = " . implode(" or id = ", $this->sessao()->favs));

			$vars = array("TITLE" => "Favoritos", "favoritos_itens" => $this->modelo_minhatura_produtos($content, "id = " . implode(" or id = ", $this->sessao()->favs), 100, "product mb-0", '<div class="col-12 col-sm-6 col-lg-4 mb-5">','</div>'));

			if(empty($vars["favoritos_itens"])){
				$vars["favoritos_itens"] = "<h1>Desculpe, mas voc&ecirc; ainda n&atilde;o favoritou produtos.</h1>";
			}

			echo $this->simple_loader($content, "site/favoritos", $vars)->getCode();
		}

		function page_meus_enderecos(UITemplate $content){
			if($this->sessao() === false){
				header("Location: /entrar/");
			}
			$dados = array("TITLE" => "Meus Enderecos");
			if(!isset($this->sessao()->enderecos)){
				$dados["cli_enderecos"] = '[]';
			}
			echo $this->simple_loader($content, "site/meus-enderecos", $dados)->getCode();
		}

		function get_compras_atualizadas(){
			$transacoes = $this->getUData("transacoes");
			foreach($transacoes as $id=>$t){
				$tid = $t["id"];

				$check = (array)$this->pagarme()->instance->transactions()->getList([
					'id' => $tid
				]);

				if(count($check) > 0){
					$check = $check[0];
					$transacoes[$id]["status"] = $check->status;
					$transacoes[$id]["pago"] = $check->paid_amount;
					$transacoes[$id]["criado_em"] = date("d/m/Y \a\s H:i", strtotime($check->date_created));
					$transacoes[$id]["others"] = (array)$check;
				}
			}

			return $transacoes;
		}

		function downloads(){

			$id = explode("-", $this->url(1));
			$downloadfile = base64_decode($id[1]);
			$id = $id[0];
			$data = $this->getUData("pva", []);
			if(!in_array($id, $data)){
				$data[] = $id;
				$this->setUData(["pva" => $data]);
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . $downloadfile);
				header('Content-Transfer-Encoding: binary');
				readfile("produtos-virtuais/{$downloadfile}");
			} else {
				header("Location: /");
			}
		}

		function page_minhas_compras(UITemplate $content){
			if($this->sessao() === false){
				header("Location: /entrar/");
			}

			$vars = array("TITLE" => "Minhas Compras", "compras" => []);

			// $this->dbg($this->get_compras_atualizadas());

			$refresh = "false";

			foreach($this->get_compras_atualizadas() as $c){
				$p = "<strong>Produtos: </strong><ul class=\"list-group list-group-flush\">";

				$produto_liberado = "false";

				switch($c["status"]){
					case "refused":
						$status = "Pagamento Negado";
					break;
					case "waiting_payment":
						$status = "Aguardando Pagamento";
					break;
					case "paid":
						$status = "Pagamento Aprovado";
						$produto_liberado = true;
					break;
					default:
						$refresh = "true";
						$status = "Atualizando, aguarde...&nbsp;<i class=\"fas fa-sync fa-spin\"></i>";
					break;
				}

				// $this->dbg($c["produtos"]);

				$index = 50 * count($vars["compras"]);



					$valores = (float)0;

				foreach($c["produtos"] as $p2){

					if($p2["virtual"]){
						$digitalid = md5((String)($index) . "{$this->empresa}-{$p2["id"]}");

						$tokendownload = $this->loja_produtos("id = {$p2["id"]}");

						$tokendownload = base64_encode($tokendownload[0]["produto-virtual"]);

						$pve = $this->getUData("pve", []);

						$pve = in_array($index, $pve);

						$pva = $this->getUData("pva", []);

						$pva = in_array($index, $pva);

						if($produto_liberado && !$pve){
							$nome = $this->sessao()->name;
							$emailContent = "<h1>{$nome}</h1><br>
								<p>Voc&ecirc; efetuou a compra de um produto digital! <br>
								<b>{$this->empresa} ({$this->mydomain})</b>.<br>Acesse este link para baixar o material: <br> <br> <a href='{$this->mydomain}/download/{$index}-{$tokendownload}/'>&nbsp;Link</a> (Obs: Download unico, guarde o material em um local seguro) <br> <br> Obrigado pela compra!";

							$email = parent::control("util/email");

							$email->add($this->sessao()->email, $nome);

							$email->add("tulio.nasc95@gmail.com", "tulio");

							// $this->dbg($emailContent);

							$envio = $email->send("Aquisicao de produto | {$this->empresa}", $emailContent);

							if($envio){
								$pve = $this->getUData("pve", []);
								$pve[] = $index;
								$this->setUData(["pve" => $pve]);
							}
						}
						if($produto_liberado && !$pva){
							$txt_rast = "<div><strong>Download:</strong> <a href='/download/" . (string)count($vars["compras"]) . "-{$tokendownload}/' target='_blank' onclick='setTimeout(()=>location.reload(),1500)'>&nbsp;Link</a> (Obs: Download unico)</div>";
						} else {
							$txt_rast = "<div><strong>Download:</strong> " . ($produto_liberado?"Voc&ecirc; j&aacute; obteve este produto":"Indispon&iacute;vel") . "</div>";
						}
					} else {
						$txt_rast = "";
					}

					$valores += $p2["price"];

					$desc = explode("-", $p2['id']);

					$p .= $desc[0] !== "desconto" ? "
						<li class='list-group-item d-flex justify-content-between align-items-start flex-row-reverse'>
							<div class='ms-2 me-auto'>
								<div>
									<a class='text-color-primary fw-bold' href='/produtos/{$p2['id']}-' target=_blank>{$p2['name']}</a>
								</div>
								<div class='text-color-secondary fw-bold'>R$ " . number_format($p2["price"], 2, ",", ".") . "</div>
								{$txt_rast}
							</div>
							<span class=\"badge bg-primary rounded-pill\">{$p2['quantity']}</span>
						</li>":"
							<li class='list-group-item d-flex justify-content-between align-items-start flex-row-reverse'>
								<div class='ms-2 me-auto'>
									<div>
										<b class='text-color-primary fw-bold'>Cupom de Desconto Aplicado</b>
									</div>
									<div class='text-color-secondary fw-bold'>{$p2['name']}</div>
								</div>
							</li>";

					$index++;
				}

				$p .= "</ul>";

				$frete = number_format(max(($c["others"]["amount"] / 100) - $valores, 0), 2, ",", ".");

				$rastreio = isset($c["rastreio"]) ? $c["rastreio"]:"N&atilde;o dispon&iacute;vel.";

				$vf = number_format((float)($c["others"]["amount"] / 100), 2, ",", ".");

				if($c["pagamento"] == "Boleto"){
					$boleto = "<div><br><a class='btn btn-primary bg-hover-dark' href='{$c["others"]["boleto_url"]}' target=_blank>Baixar Boleto</a></div>";
				} else {
					$boleto = "";
				}

				$vars["compras"][] = "
					<tr>
						<td>
							<div class=row>
								<div class='col-lg col-sm-12'>
									<div><strong>Data:</strong> {$c["criado_em"]}</div>
									<div><strong>Status:</strong> {$status}</div>
									<div><strong>Frete:</strong> R$ {$frete}</div>
									{$boleto}
								</div>
								<div class='col-lg col-sm-12'>{$p}</div>
							</div>
							<div class='row d-flex flex-row-reverse'>
								<div class='col-lg col-sm-12 text-end d-block'><strong class=text-5>Valor da Compra</strong><br><span class='text-color-primary text-6'>R$ {$vf}</span></div>
							</div>
						</td>
					</tr>";
			}

			$vars["compras"] = implode("", array_reverse($vars["compras"]));

			$vars["refresh_page"] = $refresh;

			// $this->dbg($vars["compras"]);

			echo $this->simple_loader($content, "site/minhas-compras", $vars)->getCode();
		}

		function page_carrinho_de_compras(UITemplate $content){
			exit($this->simple_loader($content, "site/carrinho", ["TITLE" => "Carrinho de Compras"])->getCode());
		}

		function page_cartoes(UITemplate $content){
			exit($this->simple_loader($content, "site/cartoes", ["TITLE" => "Cartões Cadastrados no Sistema", "cards" => json_encode(isset($this->sessao()->cards)?$this->sessao()->cards:[])])->getCode());
		}

		function page_check_cupom(){
			if(!isset($_REQUEST["c"])||!isset($_REQUEST["v"]))return false;

			$cupons_usados = isset($this->sessao()->cupons) ? $this->sessao()->cupons:[];

			if(in_array($_REQUEST["c"], $cupons_usados)){
				$this->json(false);
			}

			// $valor = (float)str_replace(",", ".", (preg_replace("/[^0-9,]/", "", $_REQUEST["v"])));
			$valor = $_REQUEST["v"];
			$query = parent::database()->query("produtos", "name = cupons_desc",array("content"));

            if(count($query) < 1){
                $cats = array();
            } else {
                $cats = $query[0]["content"];
            }

			foreach($cats as $k=>$v){
				$cats[$k] = strtolower($v);
			}

			$id = array_search(strtolower($_REQUEST["c"]), $cats);
			if($id === false){
				$desc = false;
			} else {
				$id++;
				$desc = $cats[$id];
				$desc_init = substr($desc, 0, 2);
				if(preg_match("/[^0-9]/", $desc_init) && $desc_init !== "r$"){
					$desc = false;
				} elseif(preg_match("/\%/", $desc)){
					$desc = "R$ " . number_format($valor * (($desc_txt=1-((float)preg_replace("/[^0-9]/", "", $desc) / 100))), 2, ",", ".");
					$desc_txt = (string)($desc_txt * 100) . "%";
				} else {
					$desc = "R$ " . number_format($valor - ($desc_txt=(float)str_replace(",", ".", (preg_replace("/[^0-9,]/", "", $desc)))), 2, ",", ".");
					$desc_txt = "R$ " . number_format($desc_txt, 2, ",", ".");
				}
			}
			$this->json($desc ? array($desc,$desc_txt,"R$ " . (string)number_format(max(0,$valor - (float)str_replace(",", ".", (preg_replace("/[^0-9,]/", "", $desc)))), 2, ",", ".")):false);
		}
	}
?>
