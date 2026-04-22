const newCommentLog = this.IN_COMMENT_LOG.map(entry => {
  return {
    ...entry, // Copiamos todos los campos
    IN_COMMENT_DATE: entry.IN_COMMENT_DATE.substring(0, 10) // Solo cambiamos la fecha
  };
});

console.log(newCommentLog);

return newCommentLog;