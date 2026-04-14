var broker = "0";
if (this.YQP_REINSURANCE_BROKER === "" ||
    this.YQP_REINSURANCE_BROKER === null ||
    Object.keys(this.YQP_REINSURANCE_BROKER).length == 0) {
        broker = "1";
        return broker;
}