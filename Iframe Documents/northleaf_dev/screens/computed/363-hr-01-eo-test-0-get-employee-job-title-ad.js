const employee = this.employeeUsers.find(user => user.displayName === this.OFF_EMPLOYEE_NAME);

const jobTitle = employee ? employee.jobTitle : null;

return jobTitle;