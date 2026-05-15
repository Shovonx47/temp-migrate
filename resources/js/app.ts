import mappingComponent from "@/forms/componenets/mapping-component";
import smsStats from "@/forms/componenets/sms-stats";

document.addEventListener("alpine:init", () => {
    Alpine.data('mappingComponent', mappingComponent)
    Alpine.data('smsStats', smsStats)
})
