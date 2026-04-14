/*
* Calc convert Propulsion value
* by Helen Callisaya
*/
let propulsion = this.YQP_OPEN_OPTIONS_YACHT_INFORMATION.YQP_PROPULSION;
var entities = [
        ['amp', '&'],
        ['apos', '\''],
        ['#x27', '\''],
        ['#x2F', '/'],
        ['#39', '\''],
        ['#47', '/'],
        ['lt', '<'],
        ['gt', '>'],
        ['nbsp', ' '],
        ['quot', '"']
    ];

for (let i = 0, max = entities.length; i < max; ++i) 
    propulsion = propulsion.replace(new RegExp('&'+entities[i][0]+';', 'g'), entities[i][1]);

return propulsion;