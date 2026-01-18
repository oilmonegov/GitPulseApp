/**
 * Dashboard analytics types
 */

export interface DashboardSummary {
    total_commits: number;
    average_impact: number;
    lines_changed: number;
}

export interface CommitOverTime {
    date: string;
    count: number;
}

export interface CommitTypeDistribution {
    type: string;
    label: string;
    count: number;
    color: string;
}

export interface DashboardPageProps {
    summary?: DashboardSummary;
    commitsOverTime?: CommitOverTime[];
    commitTypeDistribution?: CommitTypeDistribution[];
}
