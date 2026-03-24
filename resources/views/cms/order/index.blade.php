<x-layout.app :header="$pageMeta['title']">
  <div class="max-w-6xl mx-auto">
    <div 
      x-data="orderPage"
    >
      <!-- Header -->
      <div class="max-w-7xl mx-auto">
        <div class="flex items-center text-gray-700 text-xl font-semibold mb-6">
          Orders/Active Order - 
          <span x-text="tab === 'courier' ? ' Ship by Courier' : ' Pick Up in Store'" class="ml-2"></span>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <!-- Tabs & Status Filter -->
          <div class="flex items-center border-b pb-2 mb-4 gap-2">

            <!-- Ship by Courier Tab -->
            <button @click="tab = 'courier'"
              :class="tab === 'courier' ? 'border-b-2 border-primary font-bold text-primary' : 'text-gray-500'"
              class="px-4 py-2 focus:outline-none">
                Ship by Courier
                <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded" x-text="courierMeta.total === null ? '-' : courierMeta.total"></span>
            </button>

            <!-- Pick Up in Store Tab -->
            <button @click="tab = 'pickup'"
              :class="tab === 'pickup' ? 'border-b-2 border-primary font-bold text-primary' : 'text-gray-500'"
              class="px-4 py-2 focus:outline-none">
                Pick Up in Store
                <span class="ml-1 text-xs bg-gray-200 px-2 py-0.5 rounded" x-text="pickupMeta.total === null ? '-' : pickupMeta.total"></span>
            </button>

            <!-- Filter Status -->
            <div class="ml-2" x-show="tab === 'courier' || tab === 'pickup'">
                <select x-model="status" class="border rounded px-2 py-1 text-sm" @change="tab === 'pickup' ? fetchPickupOrders() : fetchCourierOrders()">
                <template x-for="opt in statusOptions()" :key="opt.value">
                  <option :value="opt.value" :selected="status === opt.value" x-text="'Status: ' + opt.label"></option>
                </template>
              </select>
            </div>

          </div>

          <!-- Actions -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              {{-- <button @click="toggleSelectAll" class="border rounded px-3 py-1 text-sm">Select all</button> --}}
              <input type="text" x-model="search" placeholder="Search order..." class="border rounded px-3 py-1 text-sm ml-2" @keyup.enter="tab === 'pickup' ? fetchPickupOrders() : fetchCourierOrders()" />
            </div>
            {{-- <div class="flex gap-2">
              <template x-if="tab === 'courier'">
                <button class="border rounded px-3 py-1 text-sm flex items-center gap-1"><span>Print All Label</span></button>
                <button class="bg-primary text-white rounded px-3 py-1 text-sm">Request Pick-up (2)</button>
              </template>
            </div> --}}
          </div>

          <!-- Showing x of y for Ship by Courier -->
          <div class="text-xs text-gray-500 mb-3"
              x-show="tab === 'courier' && courierMeta.total !== null"
              x-text="`Showing ${courierMeta.shown} of ${courierMeta.total}`">
          </div>
          
           <!-- Showing x of y for Pick Up in Store -->
          <div class="text-xs text-gray-500 mb-3"
              x-show="tab === 'pickup' && pickupMeta.total !== null"
              x-text="`Showing ${pickupMeta.shown} of ${pickupMeta.total}`">
          </div>

          <!-- Orders List -->
          <div>

            <template x-if="tab === 'courier' && loadingCourier">
              <div class="text-center py-8 text-gray-400">Loading...</div>
            </template>

            <div x-show="tab === 'courier'">
              <template x-if="!loadingCourier && filteredCourierOrders.filter(o => o && o.id).length === 0">
                <div class="text-center py-8 text-gray-400">No orders found.</div>
              </template>

              <template x-for="(order, idx) in filteredCourierOrders.filter(o => o && o.id)" :key="order.id">
                <div class="bg-[#F7F7F7] rounded-lg p-4 mb-4 border">
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                      {{-- <input type="checkbox" x-model="order.checked" class="accent-primary"> --}}
                      <span class="font-semibold text-base" x-text="order.order_number"></span>
                      <span class="text-xs text-gray-500 ml-2" x-text="order.date"></span>
                      <span class="ml-2 text-sm font-medium" x-text="order.location"></span>
                    </div>

                    <!-- ✅ Manual review badge + button (ONLY in courier + pending + awaiting review) -->
                    <div class="flex items-center gap-2"
                        x-show="tab === 'courier' && order.status === 'pending' && order.manual_review?.awaiting_review">
                      <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                        Awaiting Review
                      </span>
                    </div>
                  </div>
                  <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-8">
                      <div>
                        <div class="text-xs text-gray-500 mb-1">Shipping Address</div>
                        <div class="text-sm font-medium whitespace-pre-line" x-text="order.shipping.address || '-' "></div>
                        <div class="flex gap-4 mt-2 text-xs">
                          <div>Courier Service<br><span class="font-semibold" x-text="order.shipping.courier_service"></span></div>
                          <template x-if="order.shipping.courier_key === 'delivery-sarinah' && order.shipping.external_courier_name">
                              <div>
                                  Courier External<br>
                                  <span class="font-semibold text-primary" x-text="order.shipping.external_courier_name"></span>
                              </div>
                          </template>
                          <div>Shipping ID<br><span class="font-semibold" x-text="order.shipping.shipping_id || '-'"></span></div>
                        </div>
                      </div>
                    </div>
                    <div class="col-span-4">
                      <div class="flex flex-col gap-2">
                        <template x-for="(item, i) in expandedItems[order.id] ? order.items : order.items.slice(0, 3)" :key="i">
                          <div class="flex gap-2 items-center border-b pb-2 last:border-b-0 last:pb-0">
                            <img :src="item.img" class="w-12 h-12 object-cover rounded" alt="">
                            <div>
                              <div class="text-xs font-semibold" x-text="item.name"></div>
                              <div class="text-xs text-gray-500" x-text="item.variant"></div>
                              <div class="text-xs"> <span x-text="item.qty"></span>x <span x-text="item.price"></span></div>
                            </div>
                          </div>
                        </template>
                        <template x-if="order.items.length > 3">
                          <button class="text-xs text-primary mt-2" @click="expandedItems[order.id] = !expandedItems[order.id]" x-text="expandedItems[order.id] ? 'Show less' : 'Show all'"></button>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="flex gap-2 mt-4">
                    <a :href="`/secretgate19/order/${order.id}/invoice?format=pdf`" target="_blank" class="border rounded px-3 py-1 text-sm flex items-center gap-1">
                      <span>Download Invoice</span>
                    </a>
                    <a :href="`/secretgate19/order/${order.id}/invoice`" target="_blank" class="border rounded px-3 py-1 text-sm flex items-center gap-1">
                      <span>Print Invoice</span>
                    </a>
                    {{-- <button class="border rounded px-3 py-1 text-sm flex items-center gap-1"><span>Detail</span></button> --}}
                    <template x-if="tab === 'courier' && order.status === 'pending' && order.manual_review?.awaiting_review">
                      <button
                        class="bg-primary text-white rounded px-3 py-1 text-sm"
                        @click="openManualReview(order)"
                        :disabled="actionLoading === order.id"
                      >
                        Review
                      </button>
                    </template>
                    <template x-if="tab === 'courier' && order.status === 'sent_to_courier' && order.shipping.courier_key === 'delivery-sarinah'">
                      <button class="bg-primary text-white rounded px-3 py-1 text-sm" :disabled="actionLoading === order.id || order.status !== 'sent_to_courier'" @click="order.status === 'sent_to_courier' ? confirmChangeStatus(order, 'preparing') : null">Set Preparing</button>
                    </template>
                    <template x-if="tab === 'courier' && order.status === 'preparing' && order.shipping.courier_key === 'delivery-sarinah'">
                      <button class="bg-primary text-white rounded px-3 py-1 text-sm" :disabled="actionLoading === order.id || order.status !== 'preparing'" @click="order.status === 'preparing' ? openCourierModal(order): null">Ready Delivery</button>
                    </template>
                    <template x-if="tab === 'courier' && order.status === 'on_delivery' && order.shipping.courier_key === 'delivery-sarinah'">
                      <button class="bg-primary text-white rounded px-3 py-1 text-sm" :disabled="actionLoading === order.id || order.status !== 'on_delivery'" @click="order.status === 'on_delivery' ? confirmChangeStatus(order, 'completed') : null">Done</button>
                    </template>
                  </div>
                </div>
              </template>
            </div>

            <!-- Pick Up in Store -->
            <template x-if="tab === 'pickup' && loadingPickup">
              <div class="text-center py-8 text-gray-400">Loading...</div>
            </template>

            <div x-show="tab === 'pickup'">
              <template x-if="!loadingPickup && filteredOrders.filter(o => o && o.id).length === 0">
                <div class="text-center py-8 text-gray-400">No orders found.</div>
              </template>

              <template x-for="(order, idx) in filteredOrders.filter(o => o && o.id && (o.order_number || (o.pickup && o.pickup.customer) || (o.shipping && (o.shipping.courier_service || o.shipping.address))))" :key="order.id">
                <div class="bg-[#F7F7F7] rounded-lg p-4 mb-4 border">
                  <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                      {{-- <input type="checkbox" x-model="order.checked" class="accent-primary"> --}}
                      <span class="font-semibold text-base" x-text="order.order_number"></span>
                      <span class="text-xs text-gray-500 ml-2" x-text="order.date"></span>
                      <span class="ml-2 text-sm font-medium" x-text="order.location"></span>
                    </div>

                    <!-- ✅ Manual review badge + button (ONLY in pickup + pending + awaiting review) -->
                    <div class="flex items-center gap-2"
                        x-show="tab === 'pickup' && order.status === 'pending' && order.manual_review?.awaiting_review">
                      <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                        Awaiting Review
                      </span>
                    </div>
                  </div>
                  <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-8">
                      <template x-if="tab === 'courier'">
                        <div>
                          <div class="text-xs text-gray-500 mb-1">Shipping Address</div>
                          <div class="text-sm font-medium whitespace-pre-line" x-text="order.shipping.address"></div>
                          <div class="flex gap-4 mt-2 text-xs">
                            <div>Shipping Method<br><span class="font-semibold" x-text="order.shipping.method"></span></div>
                            <div>Courier Service<br><span class="font-semibold" x-text="order.shipping.courier"></span></div>
                            <div>Shipping ID<br><span class="font-semibold" x-text="order.shipping.shipping_id"></span></div>
                          </div>
                        </div>
                      </template>
                      <template x-if="tab === 'pickup'">
                        <div>
                          <div class="text-xs text-gray-500 mb-1">Pick-up Address</div>
                          <div class="text-sm font-medium whitespace-pre-line" x-text="order.pickup.address"></div>
                          <div class="mt-2 text-xs">Customer Name<br><span class="font-semibold" x-text="order.pickup.customer"></span></div>
                        </div>
                      </template>
                    </div>
                    <div class="col-span-4">
                      <div class="flex flex-col gap-2">
                        <template x-for="(item, i) in expandedItems[order.id] ? order.items : order.items.slice(0, 3)" :key="i">
                          <div class="flex gap-2 items-center border-b pb-2 last:border-b-0 last:pb-0">
                            <img :src="item.img" class="w-12 h-12 object-cover rounded" alt="">
                            <div>
                              <div class="text-xs font-semibold" x-text="item.name"></div>
                              <div class="text-xs text-gray-500" x-text="item.variant"></div>
                              <div class="text-xs"> <span x-text="item.qty"></span>x <span x-text="item.price"></span></div>
                            </div>
                          </div>
                        </template>
                        <template x-if="order.items.length > 3">
                          <button class="text-xs text-primary mt-2" @click="expandedItems[order.id] = !expandedItems[order.id]" x-text="expandedItems[order.id] ? 'Show less' : 'Show all'"></button>
                        </template>
                      </div>
                    </div>
                  </div>
                  <div class="flex gap-2 mt-4">
                    <a :href="`/secretgate19/order/${order.id}/invoice?format=pdf`" target="_blank" class="border rounded px-3 py-1 text-sm flex items-center gap-1">
                      <span>Download Invoice</span>
                    </a>
                    <a :href="`/secretgate19/order/${order.id}/invoice`" target="_blank" class="border rounded px-3 py-1 text-sm flex items-center gap-1">
                      <span>Print Invoice</span>
                    </a>
                    {{-- <button class="border rounded px-3 py-1 text-sm flex items-center gap-1"><span>Detail</span></button> --}}
                    <template x-if="tab === 'pickup' && order.status === 'preparing'">
                      <button class="bg-primary text-white rounded px-3 py-1 text-sm" :disabled="actionLoading === order.id || order.status !== 'preparing'" @click="order.status === 'preparing' ? confirmChangeStatus(order, 'ready_pick_up') : null">Ready Pick-up</button>
                    </template>
                    <template x-if="tab === 'pickup' && order.status === 'ready_pick_up'">
                      <button class="bg-primary text-white rounded px-3 py-1 text-sm" :disabled="actionLoading === order.id || order.status !== 'ready_pick_up'" @click="verifyAndSetPickupDone(order)">Done</button>
                    </template>
                    <template x-if="tab === 'pickup' && order.status === 'pending' && order.manual_review?.awaiting_review">
                      <button
                        class="bg-primary text-white rounded px-3 py-1 text-sm"
                        @click="openManualReview(order)"
                        :disabled="actionLoading === order.id"
                      >
                        Review
                      </button>
                    </template>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Popup Verify Pickup Number -->
      <div x-show="pickupVerifyModal" x-cloak>
        <div class="fixed inset-0 bg-black opacity-50 z-40"></div>
        <div class="fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg w-full">
                <div class="text-xl font-semibold mb-4">Verify Pickup Number</div>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Please enter the pickup number:</label>
                        <input 
                            type="number" 
                            x-model="pickupNumber" 
                            class="border rounded px-4 py-2 w-full" 
                            placeholder="Enter Pickup Number" 
                        />
                    </div>
                </div>

                <div class="flex justify-between gap-4">
                    <button 
                        class="bg-red-500 text-white rounded px-4 py-2 text-sm" 
                        @click="pickupVerifyModal = false"
                    >
                        Cancel
                    </button>
                    <button 
                        class="bg-primary text-white rounded px-4 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed" 
                        :disabled="!pickupNumber || actionLoading"
                        @click="submitPickupVerification"
                    >
                        Verify
                    </button>
                </div>
            </div>
        </div>
      </div>

      <!-- Popup Courier External Name & Tracking Number -->
      <div x-show="courierModal" x-cloak>
        <div class="fixed inset-0 bg-black opacity-50 z-40"></div>
        <div class="fixed inset-0 flex items-center justify-center z-50">
            <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg w-full">
                <div class="text-xl font-semibold mb-4">Courier Delivery Details</div>
                
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1">Courier Name (e.g. JNE, Gojek, Driver Name)</label>
                        <input type="text" x-model="courierName" class="border rounded px-4 py-2 w-full" placeholder="Enter Courier Name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Tracking Number / Receipt</label>
                        <input type="text" x-model="trackingNumber" class="border rounded px-4 py-2 w-full" placeholder="Enter Tracking Number" />
                    </div>
                </div>

                <div class="flex justify-between gap-4">
                    <button class="bg-red-500 text-white rounded px-4 py-2 text-sm" @click="courierModal = false">Cancel</button>
                    <button class="bg-primary text-white rounded px-4 py-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed" 
                        :disabled="!courierName || !trackingNumber || actionLoading"
                        @click="submitCourierDelivery">
                        Confirm Delivery
                    </button>
                </div>
            </div>
        </div>
      </div>
 
    </div>
  </div>

  <script>
    // Alpine.js component for order page
    window.orderPage = {
      tab: 'courier',
      pickupVerifyModal: false,
      pickupNumber: '',
      courierModal: false,
      courierName: '',
      trackingNumber: '',
      selectAll: false,
      search: '',
      status: @json($defaultStatus),
      allCourierStatuses: @json($courierStatuses),
      allPickupStatuses: @json($pickupStatuses),
      actionLoading: null,
      pickupOrders: [],
      loadingPickup: false,
      // Ship by Courier state
      courierOrders: [],
      loadingCourier: false,
      courierMeta: { total: null, shown: 0, limit: 50 },
      expandedItems: {},
      pickupMeta: {
        total: null, shown: 0, limit: 50
      },

      statusOptions() {
        return this.tab === 'courier' ? this.allCourierStatuses : this.allPickupStatuses;
      },

      init() {
        // Watch Tab Changes
        this.$watch('tab', value => {
          const options = this.statusOptions();
              
            // Logika Reset Status saat pindah tab:
            // Cari apakah status saat ini ada di tab yang baru? 
            // Jika tidak ada (misal dari 'on_delivery' pindah ke pickup), reset ke default.
            const exists = options.some(opt => opt.value === this.status);
            
            if (!exists) {
              this.status = options.length > 0 ? options[0].value : 'pending';
            }
            
            if (value === 'pickup') {
              this.fetchPickupOrders();
            } else {
              this.fetchCourierOrders();
            }
          });
          // Load awal
          if (this.tab === 'pickup') {
            this.fetchPickupOrders();
          } else {
            this.fetchCourierOrders();
          }
      },
      
      get filteredOrders() {
        if (this.tab === 'pickup') {
          return this.pickupOrders;
        }
        return [];
      },

      get filteredCourierOrders() {
        if (this.tab === 'courier') {
          return this.courierOrders;
        }
        return [];
      },

      fetchPickupOrders() {
        if (this.loadingPickup) return;
        this.loadingPickup = true;
        fetch(`/secretgate19/order/pickup-orders-api?status=${this.status}&search=${encodeURIComponent(this.search)}`)
          .then(res => res.json())
          .then(res => {
            this.pickupOrders = res.data || [];
            this.pickupMeta = res.meta || { total: this.pickupOrders.length, shown: this.pickupOrders.length, limit: 50 };
            this.loadingPickup = false;
          })
          .catch(err => {
            this.loadingPickup = false;
          });
      },

      fetchCourierOrders() {
        if (this.loadingCourier) return;
        this.loadingCourier = true;
        fetch(`/secretgate19/order/courier-orders-api?status=${this.status}&search=${encodeURIComponent(this.search)}`)
          .then(res => res.json())
          .then(res => {
            this.courierOrders = res.data || [];
            this.courierMeta = res.meta || { total: this.courierOrders.length, shown: this.courierOrders.length, limit: 50 };
            this.loadingCourier = false;
          })
          .catch(err => {
            this.loadingCourier = false;
          });
      },

      toggleSelectAll() {
        this.selectAll = !this.selectAll;
        this.filteredOrders.forEach(o => o.checked = this.selectAll);
      },

      async confirmChangeStatus(order, status) {
        if (this.actionLoading) return;
        this.actionLoading = order.id;

        // Define status mapping for labels and buttons
        const statusConfig = {
          'preparing': {
            label: 'Preparing',
            confirmButton: 'Yes, set to Preparing!'
          },
          'on_delivery': {
            label: 'On Delivery',
            confirmButton: 'Yes, set to Delivery!'
          },
          'ready_pick_up': {
            label: 'Ready for Pick Up',
            confirmButton: 'Yes, set Ready!'
          },
          'completed': {
            label: 'Completed',
            confirmButton: 'Yes, set Done!'
          }
        };

        const currentConfig = statusConfig[status] || {
          label: status,
          confirmButton: 'Yes, proceed!'
        }

        Swal.fire({
          title: 'Are you sure?',
          text: `Order will be changed to ${currentConfig.label}!`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: currentConfig.confirmButton
        }).then(async (result) => {
          if (result.isConfirmed) {
            try {
              const res = await fetch(`/secretgate19/order/${order.id}/set-change-status`, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ status })
              });
              const data = await res.json();
              if (data.success) {
                await Swal.fire('Success!', 'Order status updated.', 'success');
                if (this.tab === 'pickup' && typeof this.fetchPickupOrders === 'function') {
                  await this.fetchPickupOrders();
                } else if (this.tab === 'courier' && typeof this.fetchCourierOrders === 'function') {
                  await this.fetchCourierOrders();
                }
              } else {
                Swal.fire('Error', data.message || 'Failed to update status', 'error');
              }
            } catch (e) {
              Swal.fire('Error', 'Failed to update status', 'error');
            }
            this.actionLoading = null;
          } else {
            this.actionLoading = null;
          }
        });
      },

      async openManualReview(order) {
        if (this.actionLoading) return;

        const conf = order.manual_review?.confirmation;
        if (!conf) return;

        const expected = order.payment?.expected_amount ?? 0;
        const transfer = conf.transfer_amount ?? 0;
        const mismatch = Number(expected) !== Number(transfer);

        const fmt = (n) => 'Rp ' + new Intl.NumberFormat('en-US').format(Number(n || 0));

        const receiptLink = conf.receipt_url
          ? `<a href="${conf.receipt_url}" target="_blank" class="text-blue-600 underline">Open Receipt</a>`
          : `<span class="text-gray-400">No receipt</span>`;

        const checkboxHtml = mismatch
          ? `
            <div class="mt-3 p-3 rounded bg-yellow-50 text-left text-sm">
              <div class="font-semibold text-yellow-800">Warning: Amount mismatch</div>
              <div class="text-yellow-800">Expected: <b>${fmt(expected)}</b> — Transfer: <b>${fmt(transfer)}</b></div>
              <label class="flex items-center gap-2 mt-2">
                <input id="ack_mismatch" type="checkbox">
                <span>I acknowledge the mismatch and still want to approve.</span>
              </label>
            </div>
          `
          : '';

        const html = `
          <div class="text-left text-sm">
            <div class="grid grid-cols-2 gap-2">
              <div><b>Order</b></div><div>${order.order_number}</div>
              <div><b>Attempt</b></div><div>${conf.attempt_no}</div>
              <div><b>Expected Amount</b></div><div>${fmt(expected)}</div>
              <div><b>Transfer Amount</b></div><div>${fmt(transfer)}</div>
              <div><b>Transfer Date</b></div><div>${conf.transfer_date || '-'}</div>
              <div><b>Sender Bank</b></div><div>${conf.sender_bank_name || '-'}</div>
              <div><b>Sender Name</b></div><div>${conf.sender_account_name || '-'}</div>
              <div><b>Receipt</b></div><div>${receiptLink}</div>
            </div>
            ${checkboxHtml}
          </div>
        `;

        const result = await Swal.fire({
          title: 'Review Manual Transfer',
          html,
          icon: 'info',
          showCancelButton: true,
          showDenyButton: true,
          confirmButtonText: 'Approve',
          denyButtonText: 'Reject',
          cancelButtonText: 'Close',
          focusConfirm: false,
          preConfirm: () => {
            if (mismatch) {
              const ack = document.getElementById('ack_mismatch')?.checked;
              if (!ack) {
                Swal.showValidationMessage('Please acknowledge the mismatch checkbox to approve.');
                return false;
              }
              return { ack_mismatch: true };
            }
            return { ack_mismatch: false };
          }
        });

        // Helper function to refresh only the current tab
        const refreshCurrentTab = async () => {
          if (this.tab === 'pickup' && typeof this.fetchPickupOrders === 'function') {
            await this.fetchPickupOrders();
          } else if (this.tab === 'courier' && typeof this.fetchCourierOrders === 'function') {
            await this.fetchCourierOrders();
          }
        };

        if (result.isConfirmed) {
          this.actionLoading = order.id;
          try {
            const res = await fetch(`/secretgate19/order/payment-confirmations/${conf.id}/review`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
              },
              body: JSON.stringify({
                action: 'approve',
                ack_mismatch: result.value?.ack_mismatch ? true : false
              })
            });
            const data = await res.json();
            if (data.success) {
              await Swal.fire('Approved', data.message || 'Approved successfully.', 'success');
              await refreshCurrentTab();
            } else {
              Swal.fire('Error', data.message || 'Failed to approve.', 'error');
            }
          } catch (e) {
            Swal.fire('Error', 'Failed to approve.', 'error');
          }
          this.actionLoading = null;
          return;
        }

        if (result.isDenied) {
          const rejectModal = await Swal.fire({
            title: 'Reject Confirmation',
            input: 'textarea',
            inputLabel: 'Reject reason (required)',
            inputPlaceholder: 'Type the reason...',
            inputAttributes: { 'aria-label': 'Reject reason' },
            showCancelButton: true,
            confirmButtonText: 'Reject',
            preConfirm: (val) => {
              const v = String(val || '').trim();
              if (!v) {
                Swal.showValidationMessage('Reject reason is required.');
                return false;
              }
              return v;
            }
          });

          if (!rejectModal.isConfirmed) return;

          this.actionLoading = order.id;
          try {
            const res = await fetch(`/secretgate19/order/payment-confirmations/${conf.id}/review`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
              },
              body: JSON.stringify({
                action: 'reject',
                reject_reason: rejectModal.value
              })
            });
            const data = await res.json();
            if (data.success) {
              await Swal.fire('Rejected', data.message || 'Rejected successfully.', 'success');
              await refreshCurrentTab();
            } else {
              Swal.fire('Error', data.message || 'Failed to reject.', 'error');
            }
          } catch (e) {
            Swal.fire('Error', 'Failed to reject.', 'error');
          }
          this.actionLoading = null;
        }
      },

      verifyAndSetPickupDone(order) {
        this.pickupVerifyModal = true;
        this.pickupNumber = '';
        this.selectedOrder = order;
      },

      openCourierModal(order) {
          this.selectedOrder = order;
          this.courierName = ''; // Reset input
          this.trackingNumber = ''; // Reset input
          this.courierModal = true;
      },
      
      async submitPickupVerification() {
        if (this.actionLoading) return;
        this.actionLoading = this.selectedOrder.id;

        // Send request to verify pickup number
        const response = await fetch(`/secretgate19/order/${this.selectedOrder.id}/verify-pickup-number`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
          },
          body: JSON.stringify({ pickup_number: this.pickupNumber })
        });

        const data = await response.json();

        if (data.success) {
          // this.selectedOrder.status = 'completed'; // Update the order status in the UI
          await Swal.fire('Success!', 'Order status updated to completed.', 'success');
          if (typeof this.fetchPickupOrders === 'function') {
            await this.fetchPickupOrders();
          }
        } else {
          Swal.fire('Error', data.message || 'Invalid pickup number.', 'error');
        }

        this.pickupVerifyModal = false;
        this.actionLoading = null;
      },

      async submitCourierDelivery() {
        // Client-side validation
        if (!this.courierName || !this.trackingNumber) {
            Swal.fire('Error', 'Please fill in both Courier Name and Tracking Number.', 'error');
            return;
        }

        if (this.actionLoading) return;
        this.actionLoading = this.selectedOrder.id;

        try {
            const response = await fetch(`/secretgate19/order/${this.selectedOrder.id}/set-on-delivery`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ 
                    external_courier_name: this.courierName,
                    tracking_number: this.trackingNumber
                })
            });

            const data = await response.json();

            if (data.success) {
                // Update UI status to match Model::STATUS key
                // this.selectedOrder.status = 'on_delivery';
                this.courierModal = false;
                await Swal.fire('Success!', data.message, 'success');
                if (typeof this.fetchCourierOrders === 'function') {
                  await this.fetchCourierOrders();
                }
            } else {
                Swal.fire('Error', data.message || 'Failed to update delivery info.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'System error occurred.', 'error');
        } finally {
            this.actionLoading = null;
        }
      },
    };
  </script>
</x-layout.app>