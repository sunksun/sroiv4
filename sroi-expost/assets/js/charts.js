// SROI Ex-post Analysis Charts

// สีสำหรับกราฟ
const chartColors = {
    primary: 'rgba(102, 126, 234, 0.8)',
    secondary: 'rgba(118, 75, 162, 0.8)',
    success: 'rgba(86, 171, 47, 0.8)',
    warning: 'rgba(240, 147, 251, 0.8)',
    danger: 'rgba(245, 87, 108, 0.8)',
    info: 'rgba(78, 205, 196, 0.8)'
};

// สร้างกราฟเปรียบเทียบต้นทุนและผลประโยชน์
function createCostBenefitChart(costs, benefits, years) {
    const ctx = document.getElementById('costBenefitChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: years,
            datasets: [{
                label: 'ต้นทุน',
                data: costs,
                backgroundColor: chartColors.danger,
                borderColor: 'rgba(245, 87, 108, 1)',
                borderWidth: 2
            }, {
                label: 'ผลประโยชน์',
                data: benefits,
                backgroundColor: chartColors.success,
                borderColor: 'rgba(86, 171, 47, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'เปรียบเทียบต้นทุนและผลประโยชน์ตามปี'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// สร้างกราฟการวิเคราะห์ความอ่อนไหว
function createSensitivityChart(bestCase, baseCase, worstCase) {
    const ctx = document.getElementById('sensitivityChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['กรณีแย่ที่สุด (-20%)', 'กรณีปกติ', 'กรณีดีที่สุด (+20%)'],
            datasets: [{
                label: 'SROI Ratio',
                data: [worstCase, baseCase, bestCase],
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primary,
                fill: false,
                tension: 0.4,
                pointRadius: 8,
                pointHoverRadius: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'การวิเคราะห์ความอ่อนไหว SROI Ratio'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
}

// สร้างกราฟแยกส่วนผลประโยชน์
function createBenefitBreakdownChart(benefitsData, benefitLabels) {
    const ctx = document.getElementById('benefitBreakdownChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: benefitLabels,
            datasets: [{
                data: benefitsData,
                backgroundColor: [
                    chartColors.primary,
                    chartColors.success,
                    chartColors.info,
                    chartColors.warning,
                    chartColors.danger,
                    chartColors.secondary
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = formatCurrency(context.parsed);
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// สร้างกราฟการกระจายผลกระทบตามปี
function createImpactDistributionChart(costsData, benefitsData, years) {
    const ctx = document.getElementById('impactDistributionChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: years,
            datasets: [{
                label: 'ต้นทุนปัจจุบัน',
                data: costsData,
                borderColor: chartColors.danger,
                backgroundColor: chartColors.danger,
                fill: false,
                tension: 0.4
            }, {
                label: 'ผลประโยชน์ปัจจุบัน',
                data: benefitsData,
                borderColor: chartColors.success,
                backgroundColor: chartColors.success,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'การกระจายผลกระทบตามปี (Present Value)'
                },
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// สร้างกราหโดนัทแสดงสัดส่วนต้นทุนและผลประโยชน์
function createCostBenefitPieChart(totalCosts, totalBenefits) {
    const ctx = document.getElementById('costBenefitPieChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['ต้นทุนรวม', 'ผลประโยชน์รวม'],
            datasets: [{
                data: [totalCosts, totalBenefits],
                backgroundColor: [chartColors.danger, chartColors.success],
                borderColor: ['rgba(245, 87, 108, 1)', 'rgba(86, 171, 47, 1)'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'สัดส่วนต้นทุนและผลประโยชน์'
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = formatCurrency(context.parsed);
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// จัดรูปแบบตัวเลขสำหรับแสดงผล
function formatCurrency(value) {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

// แปลงข้อมูลให้เหมาะสำหรับ Chart.js
function prepareChartData(dataByYear, years) {
    return years.map(year => dataByYear[year] || 0);
}

// อัพเดตกราฟเมื่อเปลี่ยนค่า discount rate
function updateChartsWithDiscountRate(discountRate) {
    // คำนวณค่าใหม่ด้วย discount rate
    // อัพเดตกราฟที่เกี่ยวข้อง
    console.log('Updating charts with discount rate:', discountRate);
}

// สร้างกราฟแนวโน้ม
function createTrendChart(data, title) {
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: title,
                data: data.values,
                borderColor: chartColors.primary,
                backgroundColor: chartColors.primary,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}