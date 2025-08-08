// SROI Ex-post Analysis Main JavaScript

// ตัวแปรสำหรับเก็บค่าปัจจุบัน
let currentDiscountRate = 3.0;
let currentAnalysisPeriod = 5;
let currentProjectData = null;

// เมื่อเลือกโครงการใหม่
function selectProject(projectId) {
    if (projectId) {
        window.location.href = `?project_id=${projectId}`;
    }
}

// สร้างรายงาน
function generateReport() {
    const projectSelect = document.getElementById('projectSelect');
    const projectId = projectSelect.value;
    
    if (!projectId) {
        alert('กรุณาเลือกโครงการก่อน');
        return;
    }
    
    // แสดง loading
    showLoading();
    
    // โหลดข้อมูลและสร้างรายงาน
    setTimeout(() => {
        loadProjectData(projectId);
        hideLoading();
    }, 1000);
}

// อัพเดตอัตราคิดลด
function updateDiscountRate(value) {
    currentDiscountRate = parseFloat(value);
    document.getElementById('discountRateValue').textContent = currentDiscountRate.toFixed(1) + '%';
    document.getElementById('discountRateInput').textContent = currentDiscountRate.toFixed(1) + '%';
    
    // อัพเดตการคำนวณและกราฟ
    if (currentProjectData) {
        updateCalculations();
    }
}

// อัพเดตการวิเคราะห์
function updateAnalysis() {
    const analysisPeriod = document.getElementById('analysisPeriod').value;
    currentAnalysisPeriod = parseInt(analysisPeriod);
    
    if (currentProjectData) {
        updateCalculations();
    }
}

// อัพเดตการคำนวณ
function updateCalculations() {
    // คำนวณค่าใหม่ด้วยพารามิเตอร์ปัจจุบัน
    // อัพเดตการแสดงผล
    console.log('Updating calculations with discount rate:', currentDiscountRate);
    console.log('Analysis period:', currentAnalysisPeriod);
}

// โหลดข้อมูลโครงการ
function loadProjectData(projectId) {
    // จำลองการโหลดข้อมูล
    currentProjectData = {
        id: projectId,
        costs: [100000, 150000, 200000, 180000, 160000],
        benefits: [50000, 200000, 300000, 350000, 400000]
    };
    
    // สร้างกราฟ
    createCharts();
}

// สร้างกราฟทั้งหมด
function createCharts() {
    if (!currentProjectData) return;
    
    const years = ['2567', '2568', '2569', '2570', '2571'];
    
    // กราฟเปรียบเทียบต้นทุนและผลประโยชน์
    createCostBenefitChart(currentProjectData.costs, currentProjectData.benefits, years);
    
    // คำนวณ SROI สำหรับ sensitivity analysis
    const totalCosts = currentProjectData.costs.reduce((a, b) => a + b, 0);
    const totalBenefits = currentProjectData.benefits.reduce((a, b) => a + b, 0);
    const sroiRatio = totalBenefits / totalCosts;
    
    // กราฟ sensitivity analysis
    createSensitivityChart(
        sroiRatio * 1.2, // best case
        sroiRatio,       // base case
        sroiRatio * 0.8  // worst case
    );
}

// แสดง/ซ่อน Loading
function showLoading() {
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'loadingOverlay';
    loadingDiv.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(0,0,0,0.5); display: flex; align-items: center; 
                    justify-content: center; z-index: 9999;">
            <div style="background: white; padding: 30px; border-radius: 15px; text-align: center;">
                <div style="width: 40px; height: 40px; border: 4px solid #ddd; 
                           border-top: 4px solid #667eea; border-radius: 50%; 
                           animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
                <div>กำลังสร้างรายงาน...</div>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.remove();
    }
}

// ส่งออก Excel
function exportToExcel() {
    const data = [];
    data.push(['SROI Analysis Report']);
    data.push(['Project Code', document.querySelector('.info-item span')?.textContent || '']);
    data.push(['SROI Ratio', document.querySelector('.metric-value')?.textContent || '']);
    
    const csvContent = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sroi_analysis_report.csv';
    a.click();
}

// พิมพ์รายงาน
function printReport() {
    window.print();
}

// จัดการ responsive
function makeTablesResponsive() {
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        if (window.innerWidth < 768) {
            table.style.fontSize = '0.8em';
        } else {
            table.style.fontSize = '1em';
        }
    });
}

// ภาพเคลื่อนไหว metric cards
function animateMetricCards() {
    const cards = document.querySelectorAll('.metric-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
}

// ตรวจสอบข้อมูลที่ป้อน
function validateInputs() {
    const discountRate = currentDiscountRate;
    
    if (discountRate < 0 || discountRate > 20) {
        alert('อัตราคิดลดต้องอยู่ระหว่าง 0-20%');
        return false;
    }
    return true;
}

// เพิ่ม tooltips
function addTooltips() {
    const tooltipElements = [
        { selector: '[title*="NPV"]', text: 'มูลค่าปัจจุบันสุทธิ (Net Present Value)' },
        { selector: '[title*="SROI"]', text: 'ผลตอบแทนทางสังคมจากการลงทุน (Social Return on Investment)' }
    ];

    tooltipElements.forEach(({ selector, text }) => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            el.setAttribute('title', text);
            el.style.cursor = 'help';
        });
    });
}

// Event Listeners เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่มปุ่ม Export และ Print
    addExportPrintButtons();
    
    // เริ่มต้น animations
    setTimeout(animateMetricCards, 500);
    
    // จัดการ responsive
    window.addEventListener('resize', makeTablesResponsive);
    makeTablesResponsive();
    
    // เพิ่ม tooltips
    addTooltips();
    
    // ถ้ามีโครงการเลือกอยู่แล้ว ให้สร้างกราห
    const projectSelect = document.getElementById('projectSelect');
    if (projectSelect && projectSelect.value) {
        loadProjectData(projectSelect.value);
    }
});

// เพิ่มปุ่ม Export และ Print
function addExportPrintButtons() {
    const controlsSection = document.querySelector('.controls .control-group');
    if (controlsSection) {
        const buttonContainer = document.createElement('div');
        buttonContainer.style.marginLeft = 'auto';
        buttonContainer.style.display = 'flex';
        buttonContainer.style.gap = '10px';
        
        // ปุ่ม Export
        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn';
        exportBtn.textContent = 'ส่งออก Excel';
        exportBtn.onclick = exportToExcel;
        
        // ปุ่ม Print
        const printBtn = document.createElement('button');
        printBtn.className = 'btn';
        printBtn.textContent = 'พิมพ์รายงาน';
        printBtn.onclick = printReport;
        
        buttonContainer.appendChild(exportBtn);
        buttonContainer.appendChild(printBtn);
        controlsSection.parentNode.appendChild(buttonContainer);
    }
}

console.log('🎯 SROI Ex-post Analysis initialized successfully!');