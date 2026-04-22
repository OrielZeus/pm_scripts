const manager = this.employeeUsers.find(user => user.displayName === this.OFF_MANAGER);

const managerEmail = manager ? manager.mail : null;

return managerEmail;