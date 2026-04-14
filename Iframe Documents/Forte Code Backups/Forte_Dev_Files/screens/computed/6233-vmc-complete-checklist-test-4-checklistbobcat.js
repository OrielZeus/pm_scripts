const src = Array.isArray(this.checklist) ? this.checklist : [];
return src.filter(r => String((r.groupCode||'')).toUpperCase() === 'C');