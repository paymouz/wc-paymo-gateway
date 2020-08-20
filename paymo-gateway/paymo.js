var PaymoPaymentForm = function(lang, account, sign, theme, colors, success_url) {


  var params_main = {
    parent_id: "parent-frame",
    store_id: paymo_params.store_id,
    account: account,
    success_redirect: success_url,
    fail_redirect: success_url,
    version: "2.0.0",
    amount: paymo_params.total,
    details: "Оплата в интернет-магазине",
    lang: lang,
    key: sign,
    theme: theme,
  };

  var params_color = {
    color1: colors[0],
    color2: colors[1],
    color3: colors[2],
    color4: colors[3],
    color5: colors[4],
    color6: colors[5],
    color7: colors[6],
    color8: colors[7],
    color9: colors[8],
    color10: colors[9]
  };

  var params = (theme == 'custom') ? Object.assign(params_main, params_color) : params_main;

  paymo_open_widget(params);

  return false;

};