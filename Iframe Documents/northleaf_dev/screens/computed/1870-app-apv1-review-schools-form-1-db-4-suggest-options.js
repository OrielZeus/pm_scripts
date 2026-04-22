let a = this.trigger_suggestions;
const action = this.modalAction;
if(a != 1 && (action == 'Cancel' ||  action == 'Save') && this.suggest_options == null){
  return null;
}

return window.institutions;