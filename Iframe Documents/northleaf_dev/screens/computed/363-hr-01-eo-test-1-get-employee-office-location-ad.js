const employee = this.employeeUsers.find(user => user.displayName === this.OFF_EMPLOYEE_NAME);

const officeLocation = employee ? employee.officeLocation : null;

const filteredValue = this.OFF_OFFICE_LOCATION_OPTIONS.filter(office => office.OFFICE_DESCRIPTION === officeLocation);

return filteredValue[0];