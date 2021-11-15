LWDKExec(() => {
	window.getImov = function (getHTML = false) {
        imoveis = [];
        $(".imovel").each(function () {
            e = MapKeyAssign(
                MapEl(
                    $((el = this)).find("[data-name]"),
                    function () {
                        return [$(this).data("name"), $(this).val()];
                    },
                    !1,
                    !1
                )
            );
            imoveis[imoveis.length] = e;
            getHTML && (imoveis[imoveis.length - 1]["el"] = el);
        });
        return imoveis;
    };

    window.getCli = function () {
        return MapKeyAssign(
            MapEl(
                $(".cli[data-name]"),
                function () {
                    return [$(this).data("name"), $(this).val()];
                },
                !1,
                !1
            )
        );
    };

    window.getId = function () {
        return "{myid}";
    };

    window.getConj = function () {
        return $(".conj.m--hide").length > 1
            ? "not"
            : MapKeyAssign(
                  MapEl(
                      $(".conj [data-name]"),
                      function () {
                          return [$(this).data("name"), $(this).val()];
                      },
                      !1,
                      !1
                  )
              );
    };

    window.setValues = function setValues(data, to = "html", repeater = false) {
        if (repeater) {
            for (let j = 0; j < data.length - 1; j++) {
                $("[data-repeater-create]").each(function () {
                    this.click();
                });
            }
        }

        for (let j = 0; j < data.length; j++) {
            for (let k in data[j]) {
				if(typeof data[j][k] == "string" && data[j][k].length > 0 && data[j][k] !== "-" && data[j][k] !== " "){
	                if (repeater) {
	                    (e=$("[data-repeater-item]")
	                        .eq(j)
	                        .find('[data-name="' + k + '"]'))
	                        .val(data[j][k]);
	                } else {
	                    (e=$(to + ' [data-name="' + k + '"]')).val(data[j][k]);
	                }

					if(e.hasClass("m_selectpicker")){
						e.selectpicker('val', data[j][k]);
					} else {
						(typeof (e.attr("onchange")) != "undefined") && (e.trigger("change"));
					}
				} else {
					// console.log(k + ": ", data[j][k]);
				}
            }
        }
    };

	// $(".parcela-input input[type=\"text\"]").keyup(atualizar_valores_parcelas);

    $(".submit").click(function(){
        let data = [getId(), getCli(), getConj(), getImov(), "{modo}"];
        $.post("/ajax_cliente/", { data: data }, function (success) {
            if (success === true) {
                successRequest(() => (window.scrollTo(0, 0), "{modo}" == "mod" ? null : (top.location.href = "/cliente/")), "O cliente foi " + ("{modo}" == "mod" ? "atualizado" : "criado") + " com sucesso!");
            } else {
                // console.log(success);
                errorRequest();
            }
        });
    });

    window.atualizar_valor_final = function () {
        function mtf(valor) {
            if (valor === "") {
                valor = 0;
            } else {
                valor = valor.replace(".", "");
                valor = valor.replace(",", ".");
                valor = valor.split(/[A-z\$]/).join("");
                // console.log(valor);
                valor = parseFloat(valor);
                // console.log(valor);
            }
            return valor;
        }

		function mny(_in){
			return _in.toLocaleString("pt-br", { style: "currency", currency: "BRL" });
		}

		function get(of,the){
			return $(of).find(`[data-name="${the}"]`);
		}

        for (imov of getImov(true)) {
            $.post("/vendedores_comissao/", {v : imov["vendedor"]}, function(comissao){
				let venda = mtf(imov["valor-venda"]),
					parc  = imov["forma-pgto"].split(/[^0-9]/).join('');

				parc = parseInt(parc);
				if(parc == 0){
					parc = venda;
				} else {
					comissao = venda / 100 * (parseInt(comissao));
					final = venda;
					parc = final / parc;
				}

				let inv = 0, jnv = 0;

				for(inv = 1; inv <= imov["forma-pgto"].split(/[^0-9]/).join(''); inv++){
					let el = get(imov["el"], "valor-parcela-" + String(inv));
					(el.val() == "" || el.val() == "R$ 0,00" || el.val() == "R$ 0.00" || "{modo}" == "criar") && el.val(mny(parc));
					el.parent().parent().show();
				}
				// console.log(inv);
				for(jnv = inv; jnv <= 10; jnv++){
					// console.log("valor-parcela-" + String(jnv));
					get(imov["el"], "valor-parcela-" + String(jnv)).val('').parent().parent().hide();
				}

				get(imov["el"], "valor-final").val(mny(final));
			});
        }
    };

	if("{modo}" == "mod"){
		setValues({cliente});
		setValues({conjuge});
		$(".imoveis input[data-name]").each(function(){
			$(this).val('');
		});

		setValues({imoveis}, ".imoveis", true);
		setTimeout(() => {
			$("[data-name]:not([type=\"date\"])").each(function(){
				if(this.value.length == 0 || this.value == "-" || this.value == " "){
					v = $(this).hasClass("money") ? "R$ 0,00":"-";
					// $(this).hasClass("money") && console.log(v);
					$(this).hasClass("money") && (this.inputmask.remove());
					$(this).val(v);
				}
			});

			$("[data-name][type=\"date\"]").each(function(){
				if(this.value.length == 0 || this.value == "-" || this.value == " "){
					this.value = "{data}";
				}
			});

			LWDKInitFunction.exec();
		}, 0);

		setValues({imoveis}, ".imoveis", true);

		console.log({imoveis});
	}
});
