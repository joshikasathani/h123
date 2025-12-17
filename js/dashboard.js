// Dashboard JavaScript
class DashboardManager {
    constructor() {
        this.refreshInterval = 30000; // 30 seconds
        this.isLoading = false;
        this.init();
    }
    
    init() {
        this.loadDashboardData();
        this.startAutoRefresh();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Manual refresh button
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshDashboard();
            });
        }
    }
    
    async loadDashboardData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch('backend/dashboard-stats.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardStats(data.data);
                this.updateRecentActivities(data.data.recentActivities);
                this.updateCharts(data.data);
            } else {
                this.showError('Failed to load dashboard data');
            }
        } catch (error) {
            this.showError('Error connecting to server');
            console.error('Dashboard error:', error);
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    updateDashboardStats(data) {
        // Update stat cards with animation
        this.animateNumber('total-hospitals', data.totalHospitals);
        this.animateNumber('total-appointments', data.totalAppointments);
        this.animateNumber('today-appointments', data.todayAppointments);
        this.animateNumber('completed-appointments', data.completedAppointments);
        this.animateNumber('pending-appointments', data.pendingAppointments);
        
        // Update revenue (special handling for currency)
        const revenueElement = document.getElementById('total-revenue');
        if (revenueElement) {
            revenueElement.textContent = data.totalRevenue;
        }
    }
    
    animateNumber(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const startValue = parseInt(element.textContent) || 0;
        const duration = 1000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
            element.textContent = currentValue;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    updateRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container) return;
        
        if (activities.length === 0) {
            container.innerHTML = '<p class="no-activities">No recent activities</p>';
            return;
        }
        
        const activitiesHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-text">
                    <i class="fas fa-calendar-plus"></i> ${activity.text}
                    <span class="activity-status status-${activity.status}">${activity.status}</span>
                </div>
                <div class="activity-time">
                    <i class="fas fa-clock"></i> ${activity.time}
                </div>
            </div>
        `).join('');
        
        container.innerHTML = activitiesHTML;
    }
    
    updateCharts(data) {
        this.updateHospitalChart(data.hospitalPerformance);
        this.updateWeeklyChart(data.weeklyAppointments);
    }
    
    updateHospitalChart(hospitalData) {
        const container = document.getElementById('hospital-chart');
        if (!container || !hospitalData.length) return;
        
        // Create simple bar chart visualization
        const maxAppointments = Math.max(...hospitalData.map(h => h.appointment_count));
        const chartHTML = `
            <div class="simple-chart">
                ${hospitalData.slice(0, 5).map((hospital, index) => {
                    const percentage = (hospital.appointment_count / maxAppointments) * 100;
                    return `
                        <div class="chart-bar">
                            <div class="bar-label">${hospital.hospital_name}</div>
                            <div class="bar-container">
                                <div class="bar-fill" style="width: ${percentage}%"></div>
                                <span class="bar-value">${hospital.appointment_count}</span>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        
        container.innerHTML = chartHTML;
    }
    
    updateWeeklyChart(weeklyData) {
        const container = document.getElementById('weekly-chart');
        if (!container || !weeklyData.length) return;
        
        // Create simple line chart visualization
        const maxAppointments = Math.max(...weeklyData.map(w => w.count));
        const chartHTML = `
            <div class="simple-line-chart">
                ${weeklyData.map((day, index) => {
                    const percentage = (day.count / maxAppointments) * 100;
                    const date = new Date(day.date);
                    const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                    
                    return `
                        <div class="line-chart-point" style="left: ${(index / (weeklyData.length - 1)) * 100}%; bottom: ${percentage}%">
                            <div class="point-value">${day.count}</div>
                            <div class="point-label">${dayName}</div>
                        </div>
                    `;
                }).join('')}
                <div class="line-chart-line"></div>
            </div>
        `;
        
        container.innerHTML = chartHTML;
    }
    
    refreshDashboard() {
        const refreshBtn = document.querySelector('.refresh-btn');
        const icon = refreshBtn.querySelector('i');
        
        // Add spinning animation
        icon.classList.add('fa-spin');
        
        this.loadDashboardData().then(() => {
            // Remove spinning animation
            icon.classList.remove('fa-spin');
            
            // Show success feedback
            this.showNotification('Dashboard refreshed successfully!', 'success');
        });
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.loadDashboardData();
        }, this.refreshInterval);
    }
    
    showLoading() {
        const loadingElements = document.querySelectorAll('.stat-number');
        loadingElements.forEach(element => {
            element.style.opacity = '0.5';
        });
    }
    
    hideLoading() {
        const loadingElements = document.querySelectorAll('.stat-number');
        loadingElements.forEach(element => {
            element.style.opacity = '1';
        });
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            ${message}
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new DashboardManager();
});

// Add CSS for charts and notifications
const dashboardStyles = `
    .simple-chart {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .chart-bar {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .bar-label {
        min .bar-container {
 .bar-fill .bar-value .bar-fill .simple-line-chart .line-chart-point .point-value .point-label .line-chart-line .notification .notification-success .notification-error .notification-info .status-pending .status-completed .status-cancelled .no-activities
`;
