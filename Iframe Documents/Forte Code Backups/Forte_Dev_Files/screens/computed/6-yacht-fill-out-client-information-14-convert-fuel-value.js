/*
* Calc convert Fuel value
* by Helen Callisaya
*/
let fuel = this.YQP_OPEN_OPTIONS_YACHT_INFORMATION.YQP_FUEL;
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
    fuel = fuel.replace(new RegExp('&'+entities[i][0]+';', 'g'), entities[i][1]);

return fuel;