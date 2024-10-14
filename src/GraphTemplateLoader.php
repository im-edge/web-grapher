<?php

namespace IMEdge\Web\Grapher;

use IMEdge\RrdGraph\GraphDefinition;
use IMEdge\RrdGraph\GraphDefinitionParser;

class GraphTemplateLoader
{
    /**
     * @var array<string, string>
     */
    protected array $store = [
        'cpu1' => "DEF:avg_iowait=file1.rrd:iowait:AVERAGE DEF:avg_softirq=file1.rrd:softirq:AVERAGE"
            . " DEF:avg_irq=file1.rrd:irq:AVERAGE DEF:avg_nice=file1.rrd:nice:AVERAGE"
            . " DEF:avg_steal=file1.rrd:steal:AVERAGE DEF:avg_guest=file1.rrd:guest:AVERAGE"
            . " DEF:avg_guest_nice=file1.rrd:guest_nice:AVERAGE DEF:avg_system=file1.rrd:system:AVERAGE"
            . " DEF:avg_user=file1.rrd:user:AVERAGE DEF:avg_idle=file1.rrd:idle:AVERAGE"
            . " CDEF:total=avg_iowait,avg_softirq,avg_irq,avg_nice,avg_steal,avg_guest,avg_guest_nice,avg_system,"
            . "avg_user,avg_idle,+,+,+,+,+,+,+,+,+ CDEF:factor=total,100,/"
            . " CDEF:iowait=avg_iowait,factor,/ CDEF:softirq=avg_softirq,factor,/ CDEF:irq=avg_irq,factor,/"
            . " CDEF:nice=avg_nice,factor,/ CDEF:steal=avg_steal,factor,/ CDEF:guest=avg_guest,factor,/"
            . " CDEF:guest_nice=avg_guest_nice,factor,/ CDEF:system=avg_system,factor,/ CDEF:user=avg_user,factor,/"
            . " CDEF:idle=avg_idle,factor,/"
            . " AREA:iowait#F96266AA:'  I/O Wait':STACK"
            . " AREA:softirq#F962F5AA:'  Soft Interrupt (IRQ)':STACK"
            . " AREA:irq#8362F9AA:' Interrupt (IRQ)':STACK"
            . " AREA:steal#000000AA:' Stolen Time':STACK"
            . " AREA:guest#333333AA:' Guest CPU':STACK"
            . " AREA:guest_nice#AAAAAAAA:' Niced Guest':STACK"
            . " AREA:system#F9AF62AA:' System Mode':STACK"
            . " AREA:user#F9E962AA:' User Mode':STACK"
            . " AREA:nice#8CD400AA:'  Low Priority User Mode':STACK"
            . " AREA:idle#00A7BB99:' Idle Task':STACK",
        'cpu' => "DEF:avg_iowait=file1.rrd:iowait:AVERAGE DEF:avg_softirq=file1.rrd:softirq:AVERAGE"
            . " DEF:avg_irq=file1.rrd:irq:AVERAGE DEF:avg_nice=file1.rrd:nice:AVERAGE"
            . " DEF:avg_steal=file1.rrd:steal:AVERAGE DEF:avg_guest=file1.rrd:guest:AVERAGE"
            . " DEF:avg_guest_nice=file1.rrd:guest_nice:AVERAGE DEF:avg_system=file1.rrd:system:AVERAGE"
            . " DEF:avg_user=file1.rrd:user:AVERAGE DEF:avg_idle=file1.rrd:idle:AVERAGE"
            . " CDEF:total=avg_iowait,avg_softirq,avg_irq,avg_nice,avg_steal,avg_guest,avg_guest_nice,avg_system,"
            . "avg_user,avg_idle,+,+,+,+,+,+,+,+,+"
            . " CDEF:factor=total,100,/ CDEF:iowait=avg_iowait,factor,/ CDEF:softirq=avg_softirq,factor,/"
            . " CDEF:irq=avg_irq,factor,/ CDEF:nice=avg_nice,factor,/ CDEF:steal=avg_steal,factor,/"
            . " CDEF:guest=avg_guest,factor,/ CDEF:guest_nice=avg_guest_nice,factor,/ CDEF:system=avg_system,factor,/"
            . " CDEF:user=avg_user,factor,/ CDEF:idle=avg_idle,factor,/"
            . " AREA:iowait#F96266AA:'  I/O Wait':STACK"
            . " AREA:softirq#F962F5AA:'  Soft Interrupt (IRQ)':STACK"
            . " AREA:irq#8362F9AA:' Interrupt (IRQ)':STACK"
            . " AREA:steal#000000AA:' Stolen Time':STACK"
            . " AREA:guest#333333AA:' Guest CPU':STACK"
            . " AREA:guest_nice#AAAAAAAA:' Niced Guest':STACK"
            . " AREA:system#F9AF62AA:' System Mode':STACK"
            . " AREA:user#F9E962AA:' User Mode':STACK"
            . " AREA:nice#8CD400AA:'  Low Priority User Mode':STACK"
            . " AREA:idle#00A7BB99:' Idle Task':STACK",

        'trafficSingle' => "DEF:'dsOctetsAvg'='file1.rrd':'dsOctets':AVERAGE"
            . " DEF:'dsOctetsMin'='file1.rrd':'dsOctets':MIN"
            . " DEF:'dsOctetsMax'='file1.rrd':'dsOctets':MAX"
            . " CDEF:'dsBitsAvg'=dsOctetsAvg,8,*"
            . " CDEF:'dsBitsMin'=dsOctetsMin,8,*"
            . " CDEF:'dsBitsMax'=dsOctetsMax,8,*"


            // Summaries
            . " VDEF:'octetsTotal'=dsOctetsAvg,TOTAL"
            . " VDEF:'dsHighest'=sOctetsMax,MAXIMUM"
            . " VDEF:'dsAverage'=sOctetsAvg,AVERAGE"

            . " PRINT:dsTotal:%5.4lf"
            . " PRINT:dsHighest:%5.4lf"
            . " PRINT:dsAverage:%5.4lf"
        ,

        'if_traffic' =>
            "DEF:'ifInOctetsAvg'='file1.rrd':'ifInOctets':AVERAGE"
            . " DEF:'ifInOctetsMin'='file1.rrd':'ifInOctets':MIN"
            . " DEF:'ifInOctetsMax'='file1.rrd':'ifInOctets':MAX"

            . " DEF:'ifOutOctetsAvg'='file1.rrd':'ifOutOctets':AVERAGE"
            . " DEF:'ifOutOctetsMin'='file1.rrd':'ifOutOctets':MIN"
            . " DEF:'ifOutOctetsMax'='file1.rrd':'ifOutOctets':MAX"

            . " CDEF:'ifInBitsAvg'=ifInOctetsAvg,8,*"
            . " CDEF:'ifInBitsMin'=ifInOctetsMin,8,*"
            . " CDEF:'ifInBitsMax'=ifInOctetsMax,8,*"

            . " CDEF:'ifOutBitsAvg'=ifOutOctetsAvg,8,*"
            . " CDEF:'ifOutBitsMin'=ifOutOctetsMin,8,*"
            . " CDEF:'ifOutBitsMax'=ifOutOctetsMax,8,*"

            // Areas from min to avg and from avg to max. Could be combined,
            // but this way we could apply gradients or similar
            . " CDEF:'ifInStepAvg'=ifInBitsAvg,ifInBitsMin,-"
            . " CDEF:'ifInStepMax'=ifInBitsMax,ifInBitsAvg,-"
            . " CDEF:'ifOutStepAvg'=ifOutBitsAvg,ifOutBitsMin,-"
            . " CDEF:'ifOutStepMax'=ifOutBitsMax,ifOutBitsAvg,-"

            // Mirror outbound (for mirrored max only)
            . " CDEF:'ifInBitsAvgMirrored'=ifInBitsAvg,-1,*"

            // Mirror outbound
            . " CDEF:'ifOutBitsAvgMirrored'=ifOutBitsAvg,-1,*"
            . " CDEF:'ifOutBitsMinMirrored'=ifOutBitsMin,-1,*"
            . " CDEF:'ifOutStepAvgMirrored'=ifOutStepAvg,-1,*"
            . " CDEF:'ifOutStepMaxMirrored'=ifOutStepMax,-1,*"

            // Calculate Percentiles
            . " VDEF:'ifInBitsMaxPerc95'=ifInBitsMax,95,PERCENTNAN"
            . " VDEF:'ifInBitsMaxPerc99'=ifInBitsMax,99,PERCENTNAN"

            . " VDEF:'ifOutBitsMaxPerc95'=ifOutBitsMax,95,PERCENTNAN"
            . " VDEF:'ifOutBitsMaxPerc99'=ifOutBitsMax,99,PERCENTNAN"

            // Mirror inbound Percentiles (for common max only)
            . " CDEF:'ifInBitsMaxPerc95Mirrored'=ifInBitsMax,POP,ifOutBitsMaxPerc95,-1,*"
            . " CDEF:'ifInBitsMaxPerc99Mirrored'=ifInBitsMax,POP,ifOutBitsMaxPerc99,-1,*"

            // Mirror outbound Percentiles
            . " CDEF:'ifOutBitsMaxPerc95Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc95,-1,*"
            . " CDEF:'ifOutBitsMaxPerc99Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc99,-1,*"

            // Summaries
            . " VDEF:'rxOctetsTotal'=ifInOctetsAvg,TOTAL"
            . " VDEF:'rxBitsHighest'=ifInBitsMax,MAXIMUM"
            . " VDEF:'rxBitsAverage'=ifInBitsAvg,AVERAGE"

            . " VDEF:'txOctetsTotal'=ifOutOctetsAvg,TOTAL"
            . " VDEF:'txBitsHighest'=ifOutBitsMax,MAXIMUM"
            . " VDEF:'txBitsAverage'=ifOutBitsAvg,AVERAGE"

            // Draw Percentiles
            . " LINE1:ifInBitsMaxPerc95#57985B:'95 Percentile':dashes=3,5:skipscale"
            . " LINE1:ifInBitsMaxPerc99#57985B:dashes=3,2:skipscale"

            . " LINE1:ifInBitsMaxPerc95#00000000:skipscale" // set max (w/o skipscale)
            . " LINE1:ifInBitsMaxPerc99#00000000:'99 Percentile':skipscale" // set max (w/o skipscale)

            . " LINE1:ifOutBitsMaxPerc95Mirrored#0095BF:dashes=3,5:skipscale"
            . " LINE1:ifOutBitsMaxPerc99Mirrored#0095BF:dashes=3,2:skipscale"

            . " LINE1:ifOutBitsMaxPerc95#00000000:skipscale" // set max (w/o skipscale)
            . " LINE1:ifOutBitsMaxPerc99#00000000:skipscale" // set max (w/o skipscale)

            . " AREA:ifInBitsMin#57985B22"
            . " AREA:ifInStepAvg#57985B66:STACK:skipscale"
            . " AREA:ifInStepMax#57985B66:STACK:skipscale"
            // . " AREA:ifInBitsAvg#57985B50"
            . " LINE1.2:ifInBitsAvg#57985Bff"
            . " LINE1:ifOutBitsAvg#00000000" // force max mirrored

            . " AREA:ifOutBitsMinMirrored#0095BF22"
            . " AREA:ifOutStepAvgMirrored#0095BF66:STACK:skipscale"
            . " AREA:ifOutStepMaxMirrored#0095BF66:STACK:skipscale"
            // . " AREA:ifOutBitsAvgMirrored#0095BF50"
            . " LINE1.2:ifOutBitsAvgMirrored#0095BFff"
            . " LINE1:ifInBitsAvgMirrored#00000000" // force max mirrored

            // Line at zero X
            . " HRULE:0#53535380"

            . " PRINT:rxOctetsTotal:%5.4lf"
            . " PRINT:rxBitsHighest:%5.4lf"
            . " PRINT:rxBitsAverage:%5.4lf"

            . " PRINT:txOctetsTotal:%5.4lf"
            . " PRINT:txBitsHighest:%5.4lf"
            . " PRINT:txBitsAverage:%5.4lf"

            . " PRINT:ifInBitsMaxPerc95:%5.4lf"
            . " PRINT:ifInBitsMaxPerc99:%5.4lf"

            . " PRINT:ifOutBitsMaxPerc95:%5.4lf"
            . " PRINT:ifOutBitsMaxPerc99:%5.4lf",

        'if_traffic_max' =>
            "DEF:'ifInOctetsAvg'='file1.rrd':'ifInOctets':AVERAGE"
            . " DEF:'ifInOctetsMin'='file1.rrd':'ifInOctets':MIN"
            . " DEF:'ifInOctetsMax'='file1.rrd':'ifInOctets':MAX"

            . " DEF:'ifOutOctetsAvg'='file1.rrd':'ifOutOctets':AVERAGE"
            . " DEF:'ifOutOctetsMin'='file1.rrd':'ifOutOctets':MIN"
            . " DEF:'ifOutOctetsMax'='file1.rrd':'ifOutOctets':MAX"

            . " CDEF:'ifInBitsAvg'=ifInOctetsAvg,8,*"
            . " CDEF:'ifInBitsMin'=ifInOctetsMin,8,*"
            . " CDEF:'ifInBitsMax'=ifInOctetsMax,8,*"

            . " CDEF:'ifOutBitsAvg'=ifOutOctetsAvg,8,*"
            . " CDEF:'ifOutBitsMin'=ifOutOctetsMin,8,*"
            . " CDEF:'ifOutBitsMax'=ifOutOctetsMax,8,*"

            // Areas from min to avg and from avg to max. Could be combined,
            // but this way we could apply gradients or similar
            . " CDEF:'ifInStepAvg'=ifInBitsAvg,ifInBitsMin,-"
            . " CDEF:'ifInStepMax'=ifInBitsMax,ifInBitsAvg,-"
            . " CDEF:'ifOutStepAvg'=ifOutBitsAvg,ifOutBitsMin,-"
            . " CDEF:'ifOutStepMax'=ifOutBitsMax,ifOutBitsAvg,-"

            // Mirror outbound (for mirrored max only)
            . " CDEF:'ifInBitsAvgMirrored'=ifInBitsAvg,-1,*"

            // Mirror outbound
            . " CDEF:'ifOutBitsAvgMirrored'=ifOutBitsAvg,-1,*"
            . " CDEF:'ifOutBitsMinMirrored'=ifOutBitsMin,-1,*"
            . " CDEF:'ifOutStepAvgMirrored'=ifOutStepAvg,-1,*"
            . " CDEF:'ifOutStepMaxMirrored'=ifOutStepMax,-1,*"

            // Calculate Percentiles
            . " VDEF:'ifInBitsMaxPerc95'=ifInBitsMax,95,PERCENTNAN"
            . " VDEF:'ifInBitsMaxPerc99'=ifInBitsMax,99,PERCENTNAN"

            . " VDEF:'ifOutBitsMaxPerc95'=ifOutBitsMax,95,PERCENTNAN"
            . " VDEF:'ifOutBitsMaxPerc99'=ifOutBitsMax,99,PERCENTNAN"

            // Mirror inbound Percentiles (for common max only)
            . " CDEF:'ifInBitsMaxPerc95Mirrored'=ifInBitsMax,POP,ifOutBitsMaxPerc95,-1,*"
            . " CDEF:'ifInBitsMaxPerc99Mirrored'=ifInBitsMax,POP,ifOutBitsMaxPerc99,-1,*"

            // Mirror outbound Percentiles
            . " CDEF:'ifOutBitsMaxPerc95Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc95,-1,*"
            . " CDEF:'ifOutBitsMaxPerc99Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc99,-1,*"

            // Mirror inbound max for max
            . " CDEF:'ifInBitsMaxMirrored'=ifInBitsMax,-1,*"


            // Summaries
            . " VDEF:'rxOctetsTotal'=ifInOctetsAvg,TOTAL"
            . " VDEF:'rxBitsHighest'=ifInBitsMax,MAXIMUM"
            . " VDEF:'rxBitsAverage'=ifInBitsAvg,AVERAGE"

            . " VDEF:'txOctetsTotal'=ifOutOctetsAvg,TOTAL"
            . " VDEF:'txBitsHighest'=ifOutBitsMax,MAXIMUM"
            . " VDEF:'txBitsAverage'=ifOutBitsAvg,AVERAGE"

            // Draw Percentiles
            . " LINE1:ifInBitsMaxPerc95#57985B:'95 Percentile':dashes=3,5"
            . " LINE1:ifInBitsMaxPerc99#57985B:dashes=3,2"

            . " LINE1:ifInBitsMaxPerc95#00000000" // set max (w/o skipscale)
            . " LINE1:ifInBitsMaxPerc99#00000000:'99 Percentile'" // set max (w/o skipscale)

            . " LINE1:ifOutBitsMaxPerc95Mirrored#0095BF:dashes=3,5"
            . " LINE1:ifOutBitsMaxPerc99Mirrored#0095BF:dashes=3,2"

            . " LINE1:ifOutBitsMaxPerc95#00000000" // set max (w/o skipscale)
            . " LINE1:ifOutBitsMaxPerc99#00000000" // set max (w/o skipscale)

            . " AREA:ifInBitsMin#57985B22"
            . " AREA:ifInStepAvg#57985B66:STACK"
            . " AREA:ifInStepMax#57985B66:STACK"
            // . " AREA:ifInBitsAvg#57985B50"
            . " LINE1:ifInBitsAvg#57985Bff"
            . " LINE1:ifOutBitsAvg#00000000" // force max mirrored

            . " AREA:ifOutBitsMinMirrored#0095BF22"
            . " AREA:ifOutStepAvgMirrored#0095BF66:STACK"
            . " AREA:ifOutStepMaxMirrored#0095BF66:STACK"
            // . " AREA:ifOutBitsAvgMirrored#0095BF50"
            . " LINE1:ifOutBitsAvgMirrored#0095BFff"
            . " LINE1:ifInBitsAvgMirrored#00000000" // force max mirrored
            . " LINE1:ifOutBitsMax#ff000000" // force max mirrored
            . " LINE1:ifInBitsMaxMirrored#ff000000" // force max mirrored

            // Line at zero X
            . " HRULE:0#53535380"

            . " PRINT:rxOctetsTotal:%5.4lf"
            . " PRINT:rxBitsHighest:%5.4lf"
            . " PRINT:rxBitsAverage:%5.4lf"

            . " PRINT:txOctetsTotal:%5.4lf"
            . " PRINT:txBitsHighest:%5.4lf"
            . " PRINT:txBitsAverage:%5.4lf"

            . " PRINT:ifInBitsMaxPerc95:%5.4lf"
            . " PRINT:ifInBitsMaxPerc99:%5.4lf"

            . " PRINT:ifOutBitsMaxPerc95:%5.4lf"
            . " PRINT:ifOutBitsMaxPerc99:%5.4lf",
        'if_traffic_simple' =>
            "DEF:'ifInOctetsAvg'='file1.rrd':'ifInOctets':AVERAGE"
            . " DEF:'ifInOctetsMin'='file1.rrd':'ifInOctets':MIN"
            . " DEF:'ifInOctetsMax'='file1.rrd':'ifInOctets':MAX"

            . " DEF:'ifOutOctetsAvg'='file1.rrd':'ifOutOctets':AVERAGE"
            . " DEF:'ifOutOctetsMin'='file1.rrd':'ifOutOctets':MIN"
            . " DEF:'ifOutOctetsMax'='file1.rrd':'ifOutOctets':MAX"

            . " CDEF:'ifInBitsAvg'=ifInOctetsAvg,8,*"
            . " CDEF:'ifInBitsMin'=ifInOctetsMin,8,*"
            . " CDEF:'ifInBitsMax'=ifInOctetsMax,8,*"

            . " CDEF:'ifOutBitsAvg'=ifOutOctetsAvg,8,*"
            . " CDEF:'ifOutBitsMin'=ifOutOctetsMin,8,*"
            . " CDEF:'ifOutBitsMax'=ifOutOctetsMax,8,*"

            // Areas from min to avg and from avg to max. Could be combined,
            // but this way we could apply gradients or similar
            . " CDEF:'ifInStepAvg'=ifInBitsAvg,ifInBitsMin,-"
            . " CDEF:'ifInStepMax'=ifInBitsMax,ifInBitsAvg,-"
            . " CDEF:'ifOutStepAvg'=ifOutBitsAvg,ifOutBitsMin,-"
            . " CDEF:'ifOutStepMax'=ifOutBitsMax,ifOutBitsAvg,-"

            // Mirror outbound
            . " CDEF:'ifOutBitsAvgMirrored'=ifOutBitsAvg,-1,*"
            . " CDEF:'ifOutBitsMinMirrored'=ifOutBitsMin,-1,*"
            . " CDEF:'ifOutStepAvgMirrored'=ifOutStepAvg,-1,*"
            . " CDEF:'ifOutStepMaxMirrored'=ifOutStepMax,-1,*"

            // Calculate Percentiles
            . " VDEF:'ifInBitsMaxPerc95'=ifInBitsMax,95,PERCENTNAN"
            . " VDEF:'ifInBitsMaxPerc99'=ifInBitsMax,99,PERCENTNAN"

            . " VDEF:'ifOutBitsMaxPerc95'=ifOutBitsMax,95,PERCENTNAN"
            . " VDEF:'ifOutBitsMaxPerc99'=ifOutBitsMax,99,PERCENTNAN"

            // Mirror outbound Percentiles
            . " CDEF:'ifOutBitsMaxPerc95Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc95,-1,*"
            . " CDEF:'ifOutBitsMaxPerc99Mirrored'=ifOutBitsMax,POP,ifOutBitsMaxPerc99,-1,*"

            // Summaries
            . " VDEF:'rxOctetsTotal'=ifInOctetsAvg,TOTAL"
            . " VDEF:'rxBitsHighest'=ifInBitsMax,MAXIMUM"
            . " VDEF:'rxBitsAverage'=ifInBitsAvg,AVERAGE"

            . " VDEF:'txOctetsTotal'=ifOutOctetsAvg,TOTAL"
            . " VDEF:'txBitsHighest'=ifOutBitsMax,MAXIMUM"
            . " VDEF:'txBitsAverage'=ifOutBitsAvg,AVERAGE"

            // Draw Percentiles
            // . " LINE1:ifInBitsMaxPerc95#57985B:dashes=3,5"
            // . " LINE1:ifInBitsMaxPerc99#57985B:dashes=3,2:skipscale"

            // . " LINE1:ifOutBitsMaxPerc95Mirrored#0095BF:dashes=3,5"
            // . " LINE1:ifOutBitsMaxPerc99Mirrored#0095BF:dashes=3,2:skipscale"

            . " AREA:ifInBitsMin#57985B22"
            . " AREA:ifInStepAvg#57985B66:STACK:skipscale"
            . " AREA:ifInStepMax#57985B66:STACK:skipscale"
            // . " AREA:ifInBitsAvg#57985B50"
            . " LINE1:ifInBitsAvg#57985Bff"

            . " AREA:ifOutBitsMinMirrored#0095BF22"
            . " AREA:ifOutStepAvgMirrored#0095BF66:STACK:skipscale"
            . " AREA:ifOutStepMaxMirrored#0095BF66:STACK:skipscale"
            // . " AREA:ifOutBitsAvgMirrored#0095BF50"
            . " LINE1:ifOutBitsAvgMirrored#0095BFff"

            // Line at zero X
            . " HRULE:0#53535380"

            . " PRINT:rxOctetsTotal:%5.4lf"
            . " PRINT:rxBitsHighest:%5.4lf"
            . " PRINT:rxBitsAverage:%5.4lf"

            . " PRINT:txOctetsTotal:%5.4lf"
            . " PRINT:txBitsHighest:%5.4lf"
            . " PRINT:txBitsAverage:%5.4lf"

            . " PRINT:ifInBitsMaxPerc95:%5.4lf"
            . " PRINT:ifInBitsMaxPerc99:%5.4lf"

            . " PRINT:ifOutBitsMaxPerc95:%5.4lf"
            . " PRINT:ifOutBitsMaxPerc99:%5.4lf",

        'if_packets' => "DEF:'def_average_ifInUcastPkts'='file1.rrd':'ifInUcastPkts':AVERAGE"
            . " DEF:'def_average_ifInNUcastPkts'='file1.rrd':'ifInNUcastPkts':AVERAGE"
            . " DEF:'def_average_ifOutUcastPkts'='file1.rrd':'ifOutUcastPkts':AVERAGE"
            . " DEF:'def_average_ifOutNUcastPkts'='file1.rrd':'ifOutNUcastPkts':AVERAGE"
            . " CDEF:'cdef__1'=def_average_ifInUcastPkts,1,/ CDEF:'cdef__2'=def_average_ifInNUcastPkts,1,/"
            . " CDEF:'cdef__3'=def_average_ifOutUcastPkts,1,/,-1,* CDEF:'cdef__4'=def_average_ifOutNUcastPkts,1,/,-1,*"
            . " HRULE:0#53535380"
            . " AREA:cdef__1#1FFF1899 AREA:cdef__2#FFBD1899::STACK AREA:cdef__3#FF18AC99 AREA:cdef__4#2A18FF99::STACK",
        'if_error' => "DEF:'ifInDiscards'='file1.rrd':'ifInDiscards':AVERAGE"
            . " DEF:'ifInErrors'='file1.rrd':'ifInErrors':AVERAGE"
            . " DEF:'ifInUnknownProtos'='file1.rrd':'ifInUnknownProtos':AVERAGE"
            . " DEF:'ifOutDiscards'='file1.rrd':'ifOutDiscards':AVERAGE"
            . " DEF:'ifOutErrors'='file1.rrd':'ifOutErrors':AVERAGE"
            . " CDEF:'ifOutDiscardsMirrored'=ifOutDiscards,-1,*"
            . " CDEF:'ifOutErrorsMirrored'=ifOutErrors,-1,*"
            . " HRULE:0#53535380"
            . " AREA:ifInErrors#FF0049"
            . " AREA:ifInDiscards#8A0027::STACK"
            . " AREA:ifInUnknownProtos#8A006B::STACK"
            . " AREA:ifOutErrorsMirrored#1008FF"
            . " AREA:ifOutDiscardsMirrored#0A0773::STACK",
        'entitySensor' => "DEF:'def_average_sensorValue'='file1.rrd':'sensorValue':AVERAGE"
            . " CDEF:'nullValue'=def_average_sensorValue,0,* LINE1:nullValue#00000000:skipscale"
            . " LINE1.3:def_average_sensorValue#E6B40C AREA:def_average_sensorValue#E6B40C44",
        'value' => "DEF:'def_avg_value'='file1.rrd':'value':AVERAGE CDEF:'cdef_zero'=def_avg_value,0,*"
            . " LINE1:cdef_zero#00000000 AREA:def_avg_value#42420166 LINE1:def_avg_value#424201:'Title'",
        'ping' => "DEF:'min'='file1.rrd':'rta':MIN CDEF:'zero'=min,0,* LINE1:zero#00000000 AREA:min#ffdc0044"
             . " DEF:'avg'='file1.rrd':'rta':AVERAGE CDEF:avgdiff=avg,min,- AREA:avgdiff#ff5c0066:STACK"
             . " DEF:'max'='file1.rrd':'rta':MAX CDEF:maxdiff=max,avg,- AREA:maxdiff#ff5c0066:STACK"
            . " LINE1:avg#ff5c00:'Ping Times'",
        'load' => "DEF:'load1min'='file1.rrd':'load1':MIN CDEF:'zero'=load1min,0,* LINE1:zero#00000000"
            . " AREA:load1min#F9E96266"
             . " DEF:'load1avg'='file1.rrd':'load1':AVERAGE CDEF:load1avgdiff=load1avg,load1min,-"
            . " AREA:load1avgdiff#F9E96299:STACK"
             . " DEF:'load1max'='file1.rrd':'load1':MAX CDEF:load1maxdiff=load1max,load1avg,-"
            . " AREA:load1maxdiff#F9E96299:STACK"
            . " LINE2:load1avg#F9E962:'Load 1'"
            . " DEF:'load5avg'='file1.rrd':'load5':AVERAGE LINE1:load5avg#F9AF62"
            . " DEF:'load15avg'='file1.rrd':'load15':AVERAGE LINE1:load15avg#F96266"
        ,
        'default' => "DEF:'def_avg_value'='file1.rrd':'value':AVERAGE CDEF:'cdef_zero'=def_avg_value,0,*"
            . " LINE1:cdef_zero#00000000 AREA:def_avg_value#42420166 LINE1:def_avg_value#424201:'Title'",
    ];
        //'load15' => '#F96266aa',
        //'load5'  => '#F9AF62aa',
        //'load1'  => '#F9E962aa',

    public function __construct()
    {
        $this->store['interface'] = $this->store['if_traffic'];
        $this->store['imedgeIfPackets'] = $this->store['if_packets'];
    }

    public function load(string $name): string
    {
        return $this->store[$name];
    }

    public function loadDefinition(string $name): GraphDefinition
    {
        return (new GraphDefinitionParser($this->load($name)))->parse();
    }
}
