/*****
 * CQP_STORAGE_JUMP
 * Visibility Rule
 * By Adriana Centellas
 */

let ret = true;

if (this.CQP_STORAGE_FILLED) {
    ret = this.CQP_STORAGE[0].CQP_STORAGE_CHECKBOXES_VALIDATION.valid;
}

return this.CQP_STORAGE[0].CQP_STORAGE_VALIDATION.valid && ret;