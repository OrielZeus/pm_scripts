function formatCurrency(value) {
  const num = Number(value);
  return isNaN(num)
    ? ''
    : new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(num);
}

// Format date as M/D/YYYY
function formatDate(dateStr) {
  if (!dateStr) return '';
  const [year, month, day] = dateStr.split('-');
  if (!year || !month || !day) return '';
  return `${parseInt(month)}/${parseInt(day)}/${parseInt(year)}`;
}

// Compute the 3-month exception logic
const today       = new Date();
const fromDate    = new Date(this.summaryData?.YQP_PERIOD_FROM);
const toDate      = new Date(this.summaryData?.YQP_PERIOD_TO);
const renewStart  = new Date(toDate);
renewStart.setMonth(renewStart.getMonth() - 3);

// BOUND status: only “Already quoted” if today is >= fromDate and <= three months before toDate
const boundStatus = (today >= fromDate && today <= renewStart)
  ? 'Already quoted this year'
  : 'Not quoted this year';

// RENEWAL status:
// – Within coverage: today between fromDate and three months before toDate  
// – Eligible for renewal: today between three months before toDate and toDate  
// – Otherwise: Renewable
let renewalStatus;
if (today >= fromDate && today <= renewStart) {
  renewalStatus = 'Within coverage period';
} else if (today > renewStart && today <= toDate) {
  renewalStatus = 'Eligible for renewal';
} else {
  renewalStatus = 'Renewable';
}

const modalHtml = `
  <!-- Button trigger modal with eye icon -->
  <button
    type="button"
    class="btn btn-outline-primary"
    data-toggle="modal"
    data-target="#exampleModalCenter_${this.RES_REQUEST_ID}"
  >
    <i class="fas fa-eye"></i>
  </button>

  <!-- Modal -->
  <div
    class="modal fade"
    id="exampleModalCenter_${this.RES_REQUEST_ID}"
    tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content" style="border-radius: .5rem; overflow: hidden;">
        <div class="modal-header bg-light">
          <h5 class="modal-title">Client Information</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="container-fluid">
            <!-- Basic info rows -->
            <div class="row">
              <!-- Quote Number / Client Name -->
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Quote Number</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_QUOTE_NUMBER || ''}</p>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Client Name</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_CLIENT_NAME || ''}</p>
              </div>
              <!-- Status / Vessel Name -->
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Status</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_STATUS || ''}</p>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Vessel Name</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_INTEREST_ASSURED || ''}</p>
              </div>
              <!-- Country / Language -->
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Country of Business</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_COUNTRY_BUSINESS || ''}</p>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Language</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_LANGUAGE || ''}</p>
              </div>
              <!-- Product / Sum Insured -->
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Product</small>
                <p class="font-weight-bold mb-0">${this.summaryData?.YQP_PRODUCT || ''}</p>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Sum Insured Vessel</small>
                <p class="font-weight-bold mb-0">${formatCurrency(this.summaryData?.YQP_SUM_INSURED_VESSEL)}</p>
              </div>
            </div>

            <!-- Period row -->
            <div class="row">
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Period From</small>
                <p class="font-weight-bold mb-0">${formatDate(this.summaryData?.YQP_PERIOD_FROM)}</p>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Period To</small>
                <p class="font-weight-bold mb-0">${formatDate(this.summaryData?.YQP_PERIOD_TO)}</p>
              </div>
            </div>

            <!-- Bound & Renewal Status -->
            <div class="row">
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Bound Status</small>
                <div>
                  <span class="badge ${boundStatus === 'Already quoted this year' ? 'badge-success' : 'badge-danger'}">
                    ${boundStatus}
                  </span>
                </div>
              </div>
              <div class="col-sm-6 mb-3">
                <small class="text-uppercase text-muted">Renewal Status</small>
                <div>
                  <span class="badge ${
                    renewalStatus === 'Within coverage period'   ? 'badge-success' :
                    renewalStatus === 'Eligible for renewal'     ? 'badge-info'    :
                    /* else */                                    'badge-warning'
                  }">
                    ${renewalStatus}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
`;

return modalHtml;