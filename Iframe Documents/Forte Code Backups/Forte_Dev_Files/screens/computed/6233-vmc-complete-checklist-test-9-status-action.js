function getStatusOptions() {
  return [
    { content: "OK", value: "OK" },
    { content: "NEEDS ATTENTION", value: "NEEDS_ATTENTION" },
    { content: "N/A", value: "N/A" }
  ];
}

const options = getStatusOptions();
return options;