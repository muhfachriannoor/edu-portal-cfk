$('.time-picker').mask('00:00');

flatpickr(".time-picker", {
  enableTime: true,
  noCalendar: true,
  dateFormat: "H:i", // 24-hour format with seconds (HH:mm:ss)
  time_24hr: true,
  allowInput: true,
  onOpen: function(selectedDates, dateStr, instance) {
    if (!instance.input.value) {
      const now = new Date();
      instance.setDate(now, true); // true = trigger change event
    }
  }
});