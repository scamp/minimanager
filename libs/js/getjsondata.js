function get_object_inf(objects_list, type)
{
    if(type != 'items' && type != 'spells')
        return;

    var objects = objects_list.split(',');
    var jfiles = new Array();
    var current_jfile = 0, last_jfile = -1;
    objects = objects.sort(function(a, b){
        return a - b;
    });

    for(k in objects)
    {
        if(objects[k] == 0)
            continue;
        current_jfile = Math.floor((objects[k]-1) / 50) * 50;
        if(current_jfile > last_jfile)
            jfiles.push(current_jfile);
        last_jfile = current_jfile;
    }

    for(k in jfiles)
    {
        $.getJSON('json/' + type + '/' + type + '_' + (jfiles[k]==0?'00':jfiles[k]) + '.json', function(data){
            for(i in data)
                    if(type == 'items')
                        set_item_inf(i, data[i][0], data[i][1]);
                    else
                        if(type == 'spells')
                            set_spell_inf(i, data[i]);
        });
    }

}

function set_item_inf(id, img, quality)
{
    $('img[name$=itm' + id + ']').attr({
        'src':   item_href(img.toLowerCase()),
        'class': 'icon_border_' + quality
        });
}

function set_spell_inf(id, img)
{
    $('img[name$=spell' + id + ']').attr({
        'src':   aura_href(img.toLowerCase())
        });
}

function item_href(img)
{
    return 'http://static.wowhead.com/images/icons/medium/' + img + '.jpg';
}

function aura_href(img)
{
    return 'http://static.wowhead.com/images/icons/medium/' + img + '.jpg';
}

/*
function AJAXQuery()
{
 this.constructor();
}

(
    function()
    {
        var req = null;
        AJAXQuery.prototype = {
            ////////////////////
            constructor: function(){
                if (window.XMLHttpRequest)
                  {
                      try {req = new XMLHttpRequest()} catch(e) {}
                  }
                  else if (window.ActiveXObject)
                  {
                    try
                    {
                      req = new ActiveXObject("Microsoft.XMLHTTP")
                    }
                    catch(e)
                    {}
                    if (!req)
                      try
                      {
                        req = new ActiveXObject("Msxml2.XMLHTTP")
                      }
                      catch (e)
                      {}
                  }
            },
            getData: function(href){
                req.open('GET', href, false);
                req.send(null);
                while(req.readyState < 4);
                return req.responseText;
            },
            getJSON: function(href){
                return eval(this.getData(href));
            }
            //////////////////
        }

    }
)();

*/