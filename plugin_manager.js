if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {

    var tab = $('<span>').attr('id', 'settingstabpluginmanager').addClass('tablink');
    var button = $('<a>').attr(
        'href', rcmail.env.comm_path+'&_action=plugin.plugin_manager.show'
    ).html(
        rcmail.gettext('plugin_manager', 'plugin_manager')
    ).appendTo(tab);
    button.bind('click', function(e){
        return rcmail.command('plugin.plugin_manager.show', this)
    });

    // add button and register command
    rcmail.add_element(tab, 'tabs');
    rcmail.register_command('plugin.plugin_manager.show', function(){
        rcmail.goto_url('plugin.plugin_manager.show')
    }, true);
  })
}
