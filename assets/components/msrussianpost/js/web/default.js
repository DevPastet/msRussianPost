/**
 * Created by mvoevodskiy on 28.10.15.
 */
$(function(){


    //
    // обновление города в заказе (вся фигня чтобы обновлялась сумма только после смены города, при этом не переопределяя дефолтный обработчик add)
    // дефолтный у add делает это, но только когда событие происходит на полях delivery или payment, а нам надо на city

    // добавляем свои шаблоны
    miniShop2.Callbacks.Order.addCity = miniShop2Config.Callbacks.Order.addCity = miniShop2Config.callbacksObjectTemplate();
    miniShop2.Order.callbacks.addCity = miniShop2Config.callbacksObjectTemplate();
    // создаем свой метод
    miniShop2.Order.addCity = function(key, value){
        //создаем свой обработчик на success
        var callbacks = miniShop2.Order.callbacks;
        callbacks.addCity.response.success = function() {
            // в нем обновляем сумму после того как город был обновлен в заказе
			miniShop2.Order.getcost();
		};
		// обновляем город
        var data = {
    		key: key,
			value: value
		};
		data[miniShop2.actionName] = 'order/add';
		miniShop2.send(data, miniShop2.Order.callbacks.addCity, miniShop2.Callbacks.Order.addCity);
    };
    // событие на контрол
    $('#city_select').on('change', function(){
    	miniShop2.Order.addCity('city', $(this).val());
    });

    miniShop2.Callbacks.Order.getcost.response.success = function(response) {
		// добавляем функционал дефолтного обработчика так как мы переопределили его
		$(miniShop2.Order.orderCost, miniShop2.Order.order).text(miniShop2.Utils.formatPrice(response.data['cost']));

        // получаем срок
        // из-за него кстати сказать и происходила перезагрузка страницы, вернее из-за того что плагин возвращал код страницы если был выбран другой способ доставки
        $.post('/assets/components/msrussianpost/action.php',{msrussianpost_action: "delivery/gettime"}, function(success){
            var deliverytimespan = $('#ms2_delivery_notify');
            if (deliverytimespan !== undefined) {
                if (success)  {
                    deliverytimespan.html(success);
                } else {
                    deliverytimespan.html('');
                }

            }
            //console.log(success);
        });
    };
});