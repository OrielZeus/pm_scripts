/** 
 * Modified by Telmo Chiri
*/
let userNode = this._user;
// Delete extra nodes
delete userNode.meta;
delete userNode.schedule;

return userNode;