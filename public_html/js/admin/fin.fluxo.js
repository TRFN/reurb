window.f_checksum = "";

window.apiDate = function(input){
    this.backup = input;
    this.input = new Date(typeof input !== "string" ? input : (input + "T12:00:00"));
    this.time = function(){ return this.input.getTime(); };
    this.get = function(){return this.input;};
    this._model = function(fn,val=1,op=1){
        return(this.input["set" + fn](this.input["get" + fn]() + (val*op)));
    };

    this.sumDay = function(q=1){
        return new Date(this._model("Date", q, 1));
    };

    this.sumMonth = function(q=1){
        return new Date(this._model("Month", q, 1));
    };

    this.sumYear = function(q=1){
        return new Date(this._model("Month", q*12, 1));
    };

    this.subDay = function(q=1){
        return new Date(this._model("Date", q, -1));
    };

    this.subMonth = function(q=1){
        return new Date(this._model("Month", q, -1));
    };

    this.subYear = function(q=1){
        return new Date(this._model("Month", q*12, -1));
    };
};

window.mudar_status = function(ctx){
	// 0&&console.log(ctx);
	$.post("/change_status/", {data: ctx}, ()=>$(".m-tooltip").remove());
};

window.ano_atual = "{year}";

window.menor_data = [-1,-1];
window.maior_data = [-1,-1];

window.apagar_acao = (function(the){
	let myid = $(the).parent().find("a:first").data("my-id");
	One(the, myid).click(function(){
		0&&console.log(myid);
		confirm_msg("<h4>Deseja mesmo apagar recursivamente este registro?</h4><h6>Isto poderá implicar em outros lançamentos gerados a partir deste.</h6>", function(){
			0&&console.log(myid);
			$.post("/financeiro_form_erase/",{id: myid}, function(){
				Swal.fire("","O dado foi apagado completamente.","success");
				finupdt();
			});
		});
		return false;
	});
});

window.finupdt = ((callback=-1, args=-1)=>{
	args === -1 && (args = {});

	function g_money(data){
		return parseFloat(data.split(/[^0-9\,]/).join('').split(",").join("."));
	}

	function s_money(data){
		return data.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
	}

	function ksort(obj){
	  var keys = Object.keys(obj).sort(function(a, b){return b-a})
	    , sortedObj = {};

	  for(var i in keys) {
	    sortedObj[4000-parseInt(keys[i])] = obj[keys[i]];
	  }
	  // 0&&console.log(sortedObj);
	  return Object.values(sortedObj);
	}

	$(".nav-item.nav-link:not(.mes-atual)").css({"font-weight": "400"});
	$(".nav-item.nav-link.active:not(.mes-atual)").css({"font-weight": "bold"});

	function proccess_data(data){

		if($("#anos option").length > 0 && window.ano_atual == "-"){
			window.ano_atual = $("#anos option:first").attr("value");
			$("#anos").val(ano_atual);
		}

		function atualizarTabelas(){
			for(let i = 1; i < 13; i++){
				let varr = "DataTable__" + (table_html=$(".mes-"+String(i)).find("table")).attr("id").split(/[^a-z]/).join('_');
				// 0&&console.log(varr);
				table = window[varr];

				$(".receita-" + String(i)).text("R$ 0,00");
				$(".despesas-" + String(i)).text("R$ 0,00");

				table.rows().remove().draw(true);
			}
		}

		data.checksum += window.ano_atual.split(/[^0-9]/).join('');

		if(data.checksum !== f_checksum){
			// atualizarTabelas();
			f_checksum = data.checksum;
		} else {
			data.valores.length == 0 && ($("#anos").html("<option disabled selected>dados de&nbsp;{year}</option>"),atualizarTabelas());
			return setTimeout(finupdt, 150);
		}

		saldo = 0;
		anos = {"{year}": `<option value={year}>dados de&nbsp;{year}</option>`};
		entrada = 0;
		saida = 0;

		window.dados_atuais = [];

		grafico_entrada = [];
		grafico_saida = [];
		meses_txt = ["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"];

		// atualizarTabelas();

		for(ano in data.valores){
			let __ano = ano;
			let sl = ano_atual == ano ? 'selected="selected" ':'';
			anos[__ano] = `<option ${sl} value=${__ano}>dados de ${__ano}</option>`;

			let d_ano = data.valores[ano];
			ano_atual === ano && (atualizarTabelas(),grafico_entrada=[],grafico_saida=[]);

			if(ano_atual === ano){
				for(i of meses_txt){
					grafico_entrada.push({key: i, value: 0});
					grafico_saida.push({key: i, value: 0});
				}
			}

			for(mes in d_ano.itens){
				let d_mes = d_ano.itens[mes];
				let entrada_mes = 0;
				let saida_mes = 0;
				let row = 0;

				ano_atual === ano && (grafico_dia_entrada=[],grafico_dia_saida=[]);

				if(ano_atual === ano){
					for(i = 1; i <= (new Date(ano, mes, 0)).getDate(); i++){
						grafico_dia_entrada.push({key: i < 10 ? ("0" + String(i)) : i, value: 0});
						grafico_dia_saida.push({key: i < 10 ? ("0" + String(i)) : i, value: 0});
					}
				}

				// 0&&console.log();

				// ano_atual == ano && table
				// 	.rows()
				// 	.remove()
				// 	.draw(true);

				// ano_atual == "2020" && 0&&console.log([table, ano_atual, ano]);

				for(dia in d_mes.itens){
					let d_dia = d_mes.itens[dia];

					table = window["DataTable__" + (table_html=$(".mes-"+parseInt(mes)).find("table")).attr("id").split(/[^a-z]/).join('_')];


					let entrada_dia = 0;
					let saida_dia = 0;

					for(dado of d_dia.itens){
						if(dado.sect == "saida"){
							// 0&&console.log(dado);
						}

						(typeof dado.pago == "undefined" && (dado.pago = "not"));

						// console.log(dado);

						if((typeof dado.pago != "undefined" && dado.pago == "pg")){
							switch (dado.sect) {
								case "entrada":
									entrada_mes += g_money(dado.valor);
									entrada_dia += g_money(dado.valor);
									ano_atual == ano && (entrada += g_money(dado.valor));
									saldo += g_money(dado.valor);
								break;
								case "saida":
									0&&console.log(saida_mes);
									saida_mes += g_money(dado.valor);
									saida_dia += g_money(dado.valor);
									ano_atual == ano && (saida += g_money(dado.valor));
									saldo -= g_money(dado.valor);
								break;
								default:
									0&&console.log(dado.sect);
								break;
							}
						}

						(menor_data[0] == -1 || menor_data[0] > dado.vars.date) ? (menor_data = [dado.vars.date, dado.vars.string_date]):((maior_data[0] == -1 || maior_data[0] < dado.vars.date) && (maior_data = [dado.vars.date, dado.vars.string_date]));

						// 0&&console.log(maior_data);

						let myid = dado.id;

						typeof dado.nomeText == "undefined" && (dado.cliente=dado.id,dado.vendedor="'"+dado.sect+"'",dado.imovel=-1,dado.kdta=dado.data);

						let sts = typeof dado.pago == "undefined"
							? ((dado.vars.date > dado.vars.now ? ('<div class="d-block text-center"><span class="text-uppercase badge badge-danger p-2" style="font-size: 12px; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">'+(dado.sect=="entrada"?"a entrar":"a sair")+'</span></div>'):('<div class="d-block text-center"><span class="text-uppercase badge badge-success p-2" style="font-size: 12px; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">ok</span></div>')))
							: (dado.pago == "not" ? ('<div class="d-block text-center"><span class="text-uppercase badge badge-danger p-2" style="font-size: 12px; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">PENDENTE</span></div>'):('<div class="d-block text-center"><span class="text-uppercase badge badge-success p-2" style="font-size: 12px; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">PAGO</span></div>'));
							0&&console.log("{sessao-nivel_acesso}");

						let actn = (dado.pago == "not" ? '<div class="d-inline-block text-center mb-3 mx-1" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="Mudar para Pago"><span onclick="mudar_status(['+dado.cliente+','+dado.vendedor+','+dado.imovel+',\''+dado.kdta+'\',1]); return false;" class="mt-3 text-uppercase badge badge-dark p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;"><i style="font-size: 2rem!important;" class="la la-thumbs-up"></i></span></div>'
						: '<div class="d-inline-block text-center mb-3 mx-1" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="Mudar para Pendente"><span onclick="mudar_status(['+dado.cliente+','+dado.vendedor+','+dado.imovel+',\''+dado.kdta+'\',0]); return false;" class="mt-3 text-uppercase badge badge-danger p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;"><i style="font-size: 2rem!important;" class="la la-thumbs-down"></i></span></div>');

						typeof dado.nomeText == "undefined" && (actn += '<div class="d-inline-block mt-3 text-center"><span class="acoes mt-3 d-block"><a class="d-inline-block acoes text-center mb-3 mx-1 text-uppercase badge badge-info p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;" data-skin="white" data-toggle="m-tooltip" data-placement="left" title="" data-original-title="Modificar ' + dado.sect + '" href="/financeiro_editar_'+dado.sect+'/'+dado.id+'/" data-my-id="'+dado.id+'" ajax=on><i class="la la-pencil-alt" style="font-size: 2rem!important;"></i></a><a href="#" class="d-inline-block acoes text-center mb-3 mx-1 text-uppercase badge badge-danger p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;" data-skin="white" data-toggle="m-tooltip" data-placement="bottom" title="" data-original-title="Apagar ' + dado.sect + '" onclick="apagar_acao(this); return false;" class="apagar btn btn-sm m-btn btn-outline-danger text-uppercase mx-2"><i style="font-size: 2rem!important;" class="la la-trash"></i></a></span></div>');

						if(typeof dado.nomeText != "undefined" && "{sessao-nivel_acesso}" == "Administrador"){
							let actcli = '<div class="d-inline-block text-center mb-3 mx-1" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="Modificar Cliente"><a target=_blank href="/cliente/editar/'+dado.cliente+'/" class="mt-3 text-uppercase badge badge-info p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;"><i style="font-size: 2rem!important;" class="la la-user"></i></a></div>';

							let actven = '<div class="d-inline-block text-center mb-3 mx-1" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="Modificar Vendedor"><a target=_blank href="/vendedor/editar/'+dado.vendedor+'/" class="mt-3 text-uppercase badge badge-info p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005; background-color: #559"><i style="font-size: 2rem!important;" class="la la-lightbulb"></i></a></div>';

							let actimov = '<div class="d-inline-block text-center mb-3 mx-1" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="Modificar Imóvel">     <a target=_blank href="/imovel/editar/'+dado.imovel+'/" class="mt-3 text-uppercase badge badge-info p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005; background-color: #294"><i style="font-size: 2rem!important;" class="la la-home"></i></a></div>';

							actn = actn + actcli + actven + actimov;
						}

						let dt = [
							'<b>DIA '+String(dia)+'</b>',
							dado.nome,
							dado.valor,
							'<div class="d-block text-center"><span class="text-uppercase badge badge-info p-2" style="font-size: 12px; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">'+(dado.sect=="entrada"?dado.tipo:dado.sect)+'</span></div>',
							sts,
							'<div class="d-block text-center">' + actn + '</div>'
						];
						0&&console.log(dt);
						window.dados_atuais.push([
							ano+'-'+mes+'-'+dia,
							dia+'/'+mes+'/'+ano,
							typeof dado.nomeText !== "undefined" ? dado.nomeText:dado.nome,
							dado.valor,
							(dado.sect=="entrada"?dado.tipo:dado.sect).toUpperCase(),
							typeof dado.pago == "undefined"
								? ((dado.vars.date > dado.vars.now ? ((dado.sect=="entrada"?"a entrar":"a sair")):('ok')))
								: (dado.pago == "not" ? ('PENDENTE'):('PAGO'))
						]);
						// 0&&console.log(ano_atual==ano,dt,table);
						ano_atual == ano && table.row.add(dt).draw( true );
						// row++;
					}

					ano_atual == ano && (grafico_dia_entrada[parseInt(dia)-1] = ({key: dia, value: entrada_dia}));
					ano_atual == ano && (grafico_dia_saida[parseInt(dia)-1] = ({key: dia, value: saida_dia}));
				}

				ano_atual == ano && $(".receita-" + String(parseInt(mes))).text(s_money(entrada_mes));
				ano_atual == ano && $(".despesas-" + String(parseInt(mes))).text(s_money(saida_mes));
				ano_atual == ano && $(".grafico1-" + String(parseInt(mes))).simpleBarGraph({
				  data: grafico_dia_entrada,
				  barsColor: '#36a3f7',
				  height:'170px'
				});
				// 0&&console.log(grafico_dia_saida);
				ano_atual == ano && $(".grafico2-" + String(parseInt(mes))).simpleBarGraph({
				  data: grafico_dia_saida,
				  barsColor: '#a01915',
				  height:'170px'
				});
				ano_atual == ano && (grafico_entrada[parseInt(mes)-1] = ({key: meses_txt[parseInt(mes)-1], value: entrada_mes}));
				ano_atual == ano && (grafico_saida[parseInt(mes)-1] = ({key: meses_txt[parseInt(mes)-1], value: saida_mes}));
			}
		}

		// ano = ano_atual;

		$(".ano").text(ano_atual);

		$(".saldo").text(s_money(saldo)).css({color: saldo < 0 ? "#a01915":"#36a3f7"});
		$(".receita").text(s_money(entrada));
		$(".despesas").text(s_money(saida));
		$('.grafico_entrada').simpleBarGraph({
		  data: grafico_entrada,
		  barsColor: '#36a3f7',
		  height:'250px'
		});
		$('.grafico_saida').simpleBarGraph({
		  data: grafico_saida,
		  barsColor: '#a01915',
		  height:'250px'

		});
		// 0&&console.log(ksort(anos));
		$("#anos").html(ksort(anos).join(""));
		// 0&&console.log([grafico_saida,grafico_entrada]);

		// 0&&console.log([maior_data,menor_data]);

		$("#exportacao .date").attr("max",maior_data[1]);
		$("#exportacao .date").attr("min",menor_data[1]);
		$("#exportacao .main").attr("value",menor_data[1]);
		$("#exportacao .ends").attr("value",maior_data[1]);

		LWDKInitFunction.exec();

		setTimeout(finupdt, 250);
	}

	$.post("/financeiro_data/", args, callback===-1 ? proccess_data:callback);
});

LWDKExec(()=>setTimeout(()=>(finupdt(), setTimeout(()=>$("#anos").change(function(){
	if($(this).val() === false)return;

	ano_atual = $(this).val();
	f_checksum = "upt";
}),1000)), 600));

LWDKExec(()=>$('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
   $($.fn.dataTable.tables(true)).DataTable()
      .columns.adjust();
}));

window.applyCli = [];
window.applyBank = [];

// LWDKExec(()=>$.post("/get_automatico/", (precad) => {
// 	applyCli = []; applyBank = [];
//
// 	for(dt of precad){
// 		applyCli.push(parseInt(dt.cliente));
// 		applyBank[dt.dados.id] = dt.cliente;
// 	}
//
// 	// 0&&console.log(applyCli);
// }));

window.dados_atuais = [];

window.handleFile = (function(files){
    const reader = new FileReader();
	const semelhanca = 30;
    reader.onload = (event) => {
        let data = event.target.result;
		data = data.split("CUSTAS (C)")[1];
		data = data.split("TOTAL")[0].trim();

		data = data.split("\r\n");

		for(i = 0; i < data.length; i++){
			data[i] = data[i].split('  ');
			n = [];
			for(j = 0; j < data[i].length; j++){
				data[i][j].length > 0 && n.push(data[i][j].trim());
			}
			n[1] = n[1].split(' ');
			n[1][2] = [
				("20"+(n[1][2].charAt(4)+n[1][2].charAt(5))),
				((n[1][2].charAt(2)+n[1][2].charAt(3))),
				((n[1][2].charAt(0)+n[1][2].charAt(1)))
			];
			n[1][1] = [
				("20"+(n[1][1].charAt(4)+n[1][1].charAt(5))),
				((n[1][1].charAt(2)+n[1][1].charAt(3))),
				((n[1][1].charAt(0)+n[1][1].charAt(1)))
			];

			data[i] = {
				id: n[1][0],
				nome: n[0],
				valor: parseFloat(n[2].split(/[^0-9\,]/).join('').split(",").join(".")),
				venc: `${n[1][1][2]}/${n[1][1][1]}/${n[1][1][0]}`,
				pgto: `${n[1][2][2]}/${n[1][2][1]}/${n[1][2][0]}`,
				dven: n[1][1].join("-"),
				dpgt: n[1][2].join("-")
			};

			// 0&&console.log(data[i]);

			data[i].json = JSON.stringify(data[i]);
		}

		dadosbanco = data;

		$.post("/cli_list/", (data) => {
			let aplicado = [];
			for (var dado of dadosbanco) {
				let table = `<table data-id-dado='${dado.id}' data-fixd='{"cliente":null,"dados": ${dado.json}}' class="table table-bordered"><tbody><tr><td colspan=4 class='text-center align-middle'>O pagamento identificado por</td></tr><tr><td class='text-center align-middle'><b>NOME</b></td><td class='text-center align-middle'><b>VALOR</b></td><td class='text-center align-middle'><b>PGTO</b></td><td class='text-center align-middle'><b>VENC</b></td></tr><tr><td class='text-center align-middle'>${dado.nome}</td><td class='text-center align-middle'>${dado.valor}</td><td class='text-center align-middle'>${dado.pgto}</td><td class='text-center align-middle'>${dado.venc}</td></tr><tr><td colspan=4 class='text-center align-middle'><b>Deverá ser/ atribuído para:<b></td></tr><tr><td class='text-center align-middle'><b>NOME</b></td><td class='text-center align-middle'><b>DADOS</b></td><td class='text-center align-middle'><b>EMAIL</b></td><td class='text-center align-middle'><b>AÇÕES</b></td></tr>`;

				for (var cliente of data) {
					let t = cliente.nome.toLowerCase().split(''), c, p;

					c = (dado.nome.toLowerCase().split(''));
					p = 0;

					for (si=0;si<c.length;si++) {
						// console.log(t[si],c[si]);
						(t[si]==c[si])&&(p++);
					}
					p = (p / c.length)*100;

					p > semelhanca && console.log([cliente.nome,dado.nome,p]);

					if(p > semelhanca){
						table += `<tr id='cliente-${cliente.id}' class='clientes-compativeis'><td class='text-center align-middle'><b>${cliente.nome}</b></td><td class='text-center align-middle'>
							<b>CPF:</b>&nbsp;${cliente.doc}<br>
							<b>RG:</b>&nbsp;${cliente.rg}
						</td><td class='text-center align-middle'>${cliente.email}</td><td class="text-right text-end align-middle"><button onclick="window.selected = '${cliente.id}'; setTimeout(()=>swal.clickConfirm(),500);" class="btn m-btn btn-dark m-0 vinculos" data-cliente="${cliente.id}" data-dados='${dado.json}'>Selecionar</button></td></tr>`;

						// foi.push(cliente.id);
					}
				}

				table += "</tbody></table>";
				aplicado.push(table);
			}

			let msgs = function(){
				let msg = aplicado.shift();
				window.data_auto = $(msg).data("fixd");

				// for(cli of applyCli){
				// 	msg = $(msg);
				// 	msg.find(".clientes-compativeis#cliente-" + String(cli)).remove();
				// 	msg = msg[0].outerHTML;
				// }

				if($(msg).find(".clientes-compativeis").length > 0){
					swal.fire({
						title: "Confirme o vínculo para certificar que será feita a atribuição correta",
						html: msg,
						icon: "info",
						customClass: "swal-80-size",
						showCancelButton: true,
						denyButtonColor: '#124455',
						denyButtonText: 'Não Aplicar Este Dado',
						cancelButtonColor: '#551414',
						cancelButtonText: 'Cancelar Toda a Operação',
						showConfirmButton: false,
						showDenyButton: true
					}).then((response)=>{
						// 0&&console.log(response);
						if(!response.isDismissed){
							if(response.isConfirmed){
								// cli = selected;

								data_auto.cliente = selected;

								$.post("/aplicar_automatico/", {dado: JSON.stringify(data_auto)});

								applyCli.push(selected);

								applyBank[data_auto.dados.id] = selected;

							}

							if(aplicado.length){
								setTimeout(msgs,500);
							}
						}
					});
				} else {
					let data_apply = $(msg).data("id-dado");

					if(typeof data_apply == "undefined"){
						return;
					}

					if(typeof applyBank[data_apply] !== "undefined"){
						cli = applyBank[data_apply];

						data_apply = $(msg).data("fixd");
						// 0&&console.log(data_apply);
						data_apply.cliente = cli;

						$.post("/aplicar_automatico/", {dado: JSON.stringify(data_apply)});
					}
				}
			};

			msgs();
		});
    };
    reader.readAsText(files[0]);
});

LWDKExec(()=>($('.animated-btn > i').css({"transform":"scale(1.2)","transition":"all 600ms ease"}),$('.animated-btn').css({"border-radius":"2.5rem"}).animate({opacity:1}), setTimeout(()=>$('.animated-btn').each(function(i){
	$(this).find("> span").each(function(){
		$(this).data("orig-width", $(this).width());
	});
	let t = 150;
	$(this).find("> span").delay($(this).data("anim-delay")).animate({"width":"0px"}, t);
	$(this).find("> i").delay($(this).data("anim-delay")*2).css({"transform":"scale(1.6)"});
	$(this).delay($(this).data("anim-delay")).animate({"border-radius": "100%"}, t);
	// $(this).find("> i").css({"font-size": "18px"});
	$(this).mouseenter(function(){
		$(this).find("> span").css({"width":$(this).find("> span").data("orig-width")});
		$(this).css({"border-radius": "2.5rem"});
		$(this).find("> i").css({"transform":"scale(1.2)"});
	}).mouseleave(function(){
		$(this).find("> span").animate({"width":"0px"}, 300, ()=>($(this).animate({"border-radius": "100%"}, 100),$(this).find("> i").css({"transform":"scale(1.6)"})));
	});
}),1800)));
window.config_excel = ({
	cor: "6277C2",
	titulo: encodeURIComponent("Transações relativas ao mês de".split("&ecirc;").join('ê'))
});
window.exportar_financeiro = function(){
	let cfg = GetFormData("#exportar_financeiro");

	cfg.dados = dados_atuais;

	$.post(`/financeiro_gerar_relatorio/?titulo=${config_excel.titulo}&cor=${config_excel.cor}`, cfg, function(data){
		location.href = "/" + data;
		setTimeout(()=>$.post("/deletar_excel/", {file:data}),2000);
	});
}
