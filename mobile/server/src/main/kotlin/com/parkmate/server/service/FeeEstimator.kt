package com.parkmate.server.service

import com.parkmate.server.db.FeeRuleRow
import com.parkmate.server.db.SpotRepository
import com.parkmate.server.db.SpotRow
import com.parkmate.shared.model.FeeKind
import com.parkmate.shared.model.FeePreview
import com.parkmate.shared.model.MoneyAmount

class FeeEstimator(private val spots: SpotRepository) {

    fun preview(spot: SpotRow, hours: Double? = 1.0): FeePreview {
        val osmCharge = parseOsmCharge(spot.osmCharge)
        if (osmCharge != null) {
            val estimate = hours?.let { MoneyAmount(osmCharge.amount * it, osmCharge.currency) }
            return FeePreview(
                hourlyRate = osmCharge,
                estimate = estimate,
                hours = hours,
                sourceNote = "OpenStreetMap charge tag",
                kind = kindFromParkingType(spot.parkingType),
            )
        }

        val rules = spots.feeRulesFor(spot.id, spot.city)
        val rule = pickRule(spot, rules)
        if (rule != null) {
            val rate = MoneyAmount(rule.amount, rule.currency)
            val estimate = hours?.let { MoneyAmount(rule.amount * it, rule.currency) }
            return FeePreview(
                hourlyRate = rate,
                estimate = estimate,
                hours = hours,
                sourceNote = rule.sourceNote,
                kind = FeeKind.valueOf(rule.kind),
            )
        }

        return FeePreview(
            hourlyRate = null,
            estimate = null,
            hours = hours,
            sourceNote = null,
            kind = kindFromParkingType(spot.parkingType),
        )
    }

    private fun pickRule(spot: SpotRow, rules: List<FeeRuleRow>): FeeRuleRow? {
        rules.firstOrNull { it.spotId == spot.id }?.let { return it }
        val kind = when {
            spot.parkingType.equals("multi-storey", true) ||
                spot.parkingType.equals("underground", true) -> "PARKADE"
            spot.parkingType.equals("lane", true) ||
                spot.parkingType.equals("street_side", true) -> "ON_STREET"
            else -> "LOT"
        }
        return rules.firstOrNull { it.kind == kind && it.spotId == null }
            ?: rules.firstOrNull { it.spotId == null }
    }

    private fun kindFromParkingType(parkingType: String?): FeeKind? = when (parkingType?.lowercase()) {
        "multi-storey", "underground", "rooftop" -> FeeKind.PARKADE
        "lane", "street_side", "on_kerb", "half_on_kerb" -> FeeKind.ON_STREET
        null -> null
        else -> FeeKind.LOT
    }

    private fun parseOsmCharge(raw: String?): MoneyAmount? {
        if (raw.isNullOrBlank()) return null
        val match = Regex("""([0-9]+(?:\.[0-9]+)?)\s*([A-Za-z]{3})?(?:\s*/\s*hour)?""")
            .find(raw.trim()) ?: return null
        val amount = match.groupValues[1].toDoubleOrNull() ?: return null
        val currency = match.groupValues[2].ifBlank { "USD" }.uppercase()
        return MoneyAmount(amount, currency)
    }
}
