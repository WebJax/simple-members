<!-- Tilføj en ny sektion til den akkumulerede medlemsvækst -->
<div class="sm-dashboard-card-full">
    <div class="sm-dashboard-card-header">
        <h3><?php _e('Akkumuleret medlemsudvikling', 'simple-members'); ?></h3>
        <p><?php _e('Viser den samlede udvikling i medlemstal henover det seneste år', 'simple-members'); ?></p>
    </div>
    <div class="sm-dashboard-card-content">
        <canvas id="membersGrowthChart" width="400" height="200"></canvas>
    </div>
</div>

<!-- Medlems-flow visning for at vise tilgang vs. afgang -->
<div class="sm-dashboard-card-full">
    <div class="sm-dashboard-card-header">
        <h3><?php _e('Månedlig medlemsudvikling', 'simple-members'); ?></h3>
        <p><?php _e('Viser månedlig tilgang og afgang af medlemmer', 'simple-members'); ?></p>
    </div>
    <div class="sm-dashboard-card-content">
        <canvas id="membersFlowChart" width="400" height="200"></canvas>
    </div>
</div>

<!-- Eksisterende kode for månedligt salg -->
<div class="sm-dashboard-card-full">
    // ...existing code...